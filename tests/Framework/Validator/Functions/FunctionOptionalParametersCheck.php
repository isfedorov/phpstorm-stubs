<?php

namespace StubTests\Sources\Validator\Functions;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractCallableCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that parameters optional in reflection are also optional in stub functions.
 *
 * The check is one-directional: if reflection reports a parameter as optional,
 * the stub must also declare it optional. The reverse is not enforced — stubs may
 * legitimately mark additional parameters as optional.
 *
 * A stub parameter is considered optional when:
 * - It has a default value in the signature (e.g. `$mode = SORT_REGULAR`), or
 * - It is variadic (e.g. `...$args`), or
 * - Its @param description contains [optional].
 *
 * If the stub function is not found it is silently skipped — existence is
 * FunctionExistsCheck's responsibility.
 *
 * Known problems are supported at function level:
 * - EntityType::FUNCTION + functionId + 'OptionalParametersCheck'
 *   → skips the optional-parameters check for that specific function.
 */
class FunctionOptionalParametersCheck extends AbstractCallableCheck
{
    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, EntityType::FUNCTION->value, $entityId, 'OptionalParametersCheck', $phpVersion)) {
            return $results;
        }

        $reflection   = $this->reflectionProvider->getReflection($phpVersion);
        $reflFunction = $this->findCallable($reflection, $entityId, $phpVersion);

        if ($reflFunction === null) {
            $results->addFailure($entityId, "Function {$entityId} not found in reflection data");
            return $results;
        }

        $stubFunction = $this->findCallable($stubs, $entityId, $phpVersion);

        if ($stubFunction === null) {
            // Function absent from stubs — FunctionExistsCheck's responsibility
            $results->addSuccess($entityId);
            return $results;
        }

        // Build stub parameter map by name (version-filtered, deduplicated)
        $stubParamsByName = [];
        foreach ($stubFunction->getParameters() as $param) {
            $sinceVersion   = $param->getSinceVersion();
            $removedVersion = $param->getRemovedVersion();

            $available = ($sinceVersion === null || version_compare($phpVersion, $sinceVersion, '>='))
                && ($removedVersion === null || version_compare($phpVersion, $removedVersion, '<'));

            if ($available) {
                // Last definition wins (handles placeholder + variadic same-name pairs)
                $stubParamsByName[$param->getName()] = $param;
            }
        }

        $mismatches = [];
        foreach ($reflFunction->getParameters() as $reflParam) {
            if (!$reflParam->isOptional()) {
                continue;
            }

            $name = $reflParam->getName();

            if (!isset($stubParamsByName[$name])) {
                // Missing parameter — FunctionExistsCheck / ParametersCountCheck's responsibility
                continue;
            }

            if (!$stubParamsByName[$name]->isOptional()) {
                $mismatches[] = "\${$name}";
            }
        }

        if (!empty($mismatches)) {
            $paramList = implode(', ', $mismatches);
            $results->addFailure(
                $entityId,
                "Function {$entityId}: parameter(s) optional in PHP {$phpVersion} but not in stubs: {$paramList}"
            );
            return $results;
        }

        $results->addSuccess($entityId);
        return $results;
    }
}
