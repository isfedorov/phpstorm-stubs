<?php

namespace StubTests\Sources\Validator\Functions;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractCallableCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that parameter names in stub functions/methods match those in reflection.
 *
 * Named parameters were introduced in PHP 8.0, so this check only applies to PHP >= 8.0.
 *
 * Algorithm:
 * 1. Look up the callable in both reflection and stubs using findCallable().
 * 2. If not found in stubs, silently succeed — FunctionExistsCheck handles existence.
 * 3. Filter and deduplicate stub parameters by version (merges same-named variadic pairs).
 * 4. If parameter counts differ, silently succeed — ParametersCountCheck handles that.
 * 5. Compare names positionally; collect all mismatches into one failure message.
 *
 * Known problems are supported via EntityType::FUNCTION / EntityType::METHOD (auto-detected
 * from the entityId format) with checkName 'ParameterNamesCheck'.
 */
class ParameterNamesCheck extends AbstractCallableCheck
{
    public function supports(string $phpVersion): bool
    {
        // Named parameters were introduced in PHP 8.0
        return version_compare($phpVersion, '8.0', '>=');
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        $entityType = EntityType::fromEntityId($entityId)->value;
        if ($this->skipWithKnownProblem($results, $entityType, $entityId, 'ParameterNamesCheck', $phpVersion)) {
            return $results;
        }

        $reflection = $this->reflectionProvider->getReflection($phpVersion);
        $reflCallable = $this->findCallable($reflection, $entityId, $phpVersion);

        if ($reflCallable === null) {
            $results->addFailure($entityId, "Function/method {$entityId} not found in reflection data");
            return $results;
        }

        // Stub not found — FunctionExistsCheck's responsibility
        $stubCallable = $this->findCallable($stubs, $entityId, $phpVersion);
        if ($stubCallable === null) {
            $results->addSuccess($entityId);
            return $results;
        }

        $reflParams = method_exists($reflCallable, 'getParameters') ? $reflCallable->getParameters() : [];
        $stubParams = method_exists($stubCallable, 'getParameters') ? $stubCallable->getParameters() : [];

        $stubParams = $this->filterAndDeduplicateByVersion($stubParams, $phpVersion);

        // Count mismatch — ParametersCountCheck's responsibility
        if (count($reflParams) !== count($stubParams)) {
            $results->addSuccess($entityId);
            return $results;
        }

        $mismatches = [];
        foreach ($reflParams as $index => $reflParam) {
            $reflName = method_exists($reflParam, 'getName') ? $reflParam->getName() : null;
            $stubName = isset($stubParams[$index]) && method_exists($stubParams[$index], 'getName')
                ? $stubParams[$index]->getName()
                : null;

            if ($reflName !== null && $stubName !== null && $reflName !== $stubName) {
                $mismatches[] = "#{$index}: reflection '\${$reflName}', stubs '\${$stubName}'";
            }
        }

        if (!empty($mismatches)) {
            $results->addFailure(
                $entityId,
                "Function/method {$entityId}: parameter name mismatch(es) in PHP {$phpVersion}: "
                . implode('; ', $mismatches)
            );
        } else {
            $results->addSuccess($entityId);
        }

        return $results;
    }

    /**
     * Filter parameters by version availability and merge same-named variadic pairs.
     *
     * The merge handles the stub workaround for non-optional variadic parameters:
     *   #[PhpStormStubsElementAvailable(to: '7.4')] mixed $values,
     *   mixed ...$values
     * Both survive version filtering for old PHP; treat them as one parameter.
     */
    private function filterAndDeduplicateByVersion(array $parameters, string $phpVersion): array
    {
        $filtered = [];
        foreach ($parameters as $param) {
            $since   = method_exists($param, 'getSinceVersion') ? $param->getSinceVersion() : null;
            $removed = method_exists($param, 'getRemovedVersion') ? $param->getRemovedVersion() : null;

            $available = ($since === null || version_compare($phpVersion, $since, '>='))
                && ($removed === null || version_compare($phpVersion, $removed, '<='));

            if ($available) {
                $filtered[] = $param;
            }
        }

        $merged = [];
        $count  = count($filtered);
        for ($i = 0; $i < $count; $i++) {
            $current = $filtered[$i];
            $next    = $filtered[$i + 1] ?? null;

            $currentName    = method_exists($current, 'getName') ? $current->getName() : null;
            $nextName       = $next && method_exists($next, 'getName') ? $next->getName() : null;
            $nextIsVariadic = $next && method_exists($next, 'isVariadic') && $next->isVariadic();

            if ($currentName !== null && $currentName === $nextName && $nextIsVariadic) {
                $merged[] = $next;
                $i++;
            } else {
                $merged[] = $current;
            }
        }

        return $merged;
    }
}
