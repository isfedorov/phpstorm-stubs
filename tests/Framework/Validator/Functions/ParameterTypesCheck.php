<?php

namespace StubTests\Sources\Validator\Functions;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractCallableCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\TypeHelperTrait;

/**
 * Validates that parameter types in stubs match those in reflection.
 *
 * The entityId should be in format: "FunctionName" or "ClassName::methodName"
 */
class ParameterTypesCheck extends AbstractCallableCheck
{
    use TypeHelperTrait;
    public function supports(string $phpVersion): bool
    {
        // Type hints were introduced gradually:
        // - PHP 5.0: Class type hints
        // - PHP 5.1: Array type hints
        // - PHP 7.0: Scalar type hints
        // We'll support type checking from PHP 7.0 onwards for simplicity
        return version_compare($phpVersion, '7.0', '>=');
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        // Check if this entity has a known problem that should skip validation
        $entityType = str_contains($entityId, '::') ? 'methods' : 'functions';
        if ($this->skipWithKnownProblem($results, $entityType, $entityId, 'ParameterTypesCheck', $phpVersion)) {
            return $results;
        }

        // Get the function/method from reflection
        $reflection = $this->reflectionProvider->getReflection($phpVersion);
        $reflectionCallable = $this->findCallable($reflection, $entityId, $phpVersion);

        if ($reflectionCallable === null) {
            $results->addFailure($entityId, "Function/method {$entityId} not found in reflection data");
            return $results;
        }

        // Get the function/method from stubs
        $stubCallable = $this->findCallable($stubs, $entityId, $phpVersion);

        if ($stubCallable === null) {
            $results->addFailure($entityId, "Function/method {$entityId} not found in stubs");
            return $results;
        }

        // Get parameters from both
        $reflectionParams = method_exists($reflectionCallable, 'getParameters')
            ? $reflectionCallable->getParameters()
            : [];
        $stubParams = method_exists($stubCallable, 'getParameters')
            ? $stubCallable->getParameters()
            : [];

        // Filter stub parameters by version availability
        $stubParams = $this->filterParametersByVersion($stubParams, $phpVersion);

        // Check parameter count matches
        if (count($reflectionParams) !== count($stubParams)) {
            $results->addFailure(
                $entityId,
                "Parameter count mismatch: reflection has " . count($reflectionParams) .
                " parameters, stubs have " . count($stubParams) . " parameters"
            );
            return $results;
        }

        // Compare parameter types; collect all mismatches before reporting
        $mismatches = [];
        foreach ($reflectionParams as $index => $reflectionParam) {
            $reflType = $this->getParamTypeString($reflectionParam, $phpVersion);

            // Reflection has no type — stubs may document one; skip this param.
            // This also covers PHP < 8.0 where built-in functions lacked type hints.
            if ($reflType === null) {
                continue;
            }

            $stubParam   = $stubParams[$index] ?? null;
            $stubType    = $stubParam !== null ? $this->getParamTypeString($stubParam, $phpVersion) : null;
            $normalRefl  = $this->normalizeType($reflType);
            $normalStub  = $this->normalizeType($stubType);

            if ($normalRefl !== $normalStub) {
                $paramName    = method_exists($reflectionParam, 'getName') ? $reflectionParam->getName() : "#{$index}";
                $mismatches[] = "Parameter '{$paramName}' type mismatch: reflection has '{$reflType}', " .
                    "stubs have '" . ($stubType ?? 'none') . "'";
            }
        }

        if (!empty($mismatches)) {
            $results->addFailure($entityId, implode("\n", $mismatches));
        } else {
            $results->addSuccess($entityId);
        }

        return $results;
    }

    /**
     * Resolve the effective type string for a parameter at the given PHP version.
     *
     * Priority:
     * 1. Signature type from getDeclaredType() — if non-empty (not NoType), returned directly.
     * 2. LanguageLevelTypeAware — highest version entry <= $phpVersion, or defaultType.
     *    (Only populated for stub params; reflection params have null here → returns null.)
     *
     * Returns null when no type information is available.
     */
    private function getParamTypeString(mixed $param, string $phpVersion): ?string
    {
        if (method_exists($param, 'getDeclaredType')) {
            $type       = $param->getDeclaredType();
            $typeString = method_exists($type, 'toString') ? $type->toString() : '';
            if ($typeString !== '') {
                return $typeString;
            }
        }

        $versionAwareType = $this->resolveVersionAwareType($param, $phpVersion);
        if ($versionAwareType !== null && $versionAwareType !== '') {
            return $versionAwareType;
        }

        return null;
    }

    /**
     * Filter parameters by their version availability.
     *
     * Parameters with PhpStormStubsElementAvailable attributes may have sinceVersion and removedVersion.
     * This method filters out parameters that are not available in the target PHP version.
     *
     * After filtering, this method also merges duplicate-named parameters that represent the
     * workaround for non-optional variadic parameters in PHP < 8.0.
     *
     * @param array $parameters Array of parameter objects
     * @param string $phpVersion Target PHP version (e.g., '8.0', '8.1')
     * @return array Filtered array of parameters available in the target version
     */
    private function filterParametersByVersion(array $parameters, string $phpVersion): array
    {
        $filtered = [];

        foreach ($parameters as $param) {
            $sinceVersion   = method_exists($param, 'getSinceVersion') ? $param->getSinceVersion() : null;
            $removedVersion = method_exists($param, 'getRemovedVersion') ? $param->getRemovedVersion() : null;

            $isAvailableSince = $sinceVersion === null || version_compare($phpVersion, $sinceVersion, '>=');
            $isNotRemoved     = $removedVersion === null || version_compare($phpVersion, $removedVersion, '<');

            if ($isAvailableSince && $isNotRemoved) {
                $filtered[] = $param;
            }
        }

        // Merge duplicate-named parameters (workaround for non-optional variadics)
        return $this->mergeDuplicateNamedParameters($filtered);
    }

    /**
     * Merge consecutive parameters with the same name where the second is variadic.
     *
     * This handles the stub workaround for non-optional variadic parameters in PHP < 8.0.
     * The pattern is:
     *   #[PhpStormStubsElementAvailable(from: '5.3', to: '7.4')] mixed $values,
     *   mixed ...$values
     *
     * When both parameters are present after version filtering, they represent a single
     * non-optional variadic parameter and should be merged into one.
     *
     * @param array $parameters Array of parameter objects
     * @return array Array with duplicate-named parameters merged
     */
    private function mergeDuplicateNamedParameters(array $parameters): array
    {
        $merged = [];
        $count = count($parameters);

        for ($i = 0; $i < $count; $i++) {
            $current = $parameters[$i];
            $next    = $parameters[$i + 1] ?? null;

            $currentName  = method_exists($current, 'getName') ? $current->getName() : null;
            $nextName     = $next && method_exists($next, 'getName') ? $next->getName() : null;
            $nextIsVariadic = $next && method_exists($next, 'isVariadic') && $next->isVariadic();

            if ($currentName !== null && $currentName === $nextName && $nextIsVariadic) {
                // Skip current parameter and keep only the variadic one
                $merged[] = $next;
                $i++; // Skip the next iteration since we already processed it
            } else {
                $merged[] = $current;
            }
        }

        return $merged;
    }
}
