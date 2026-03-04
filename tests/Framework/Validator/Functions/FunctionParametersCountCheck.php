<?php

namespace StubTests\Sources\Validator\Functions;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractCallableCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that the number of parameters in stub functions matches reflection.
 *
 * For each function identified by $entityId the validator:
 * 1. Looks up the function in reflection data for the given PHP version.
 * 2. Looks up the function in stubs using version-aware selection
 *    (PhpStormStubsElementAvailable `from`/`to` on the function itself).
 * 3. If the stub function is not found it is silently skipped — existence is
 *    FunctionExistsCheck's responsibility.
 * 4. When both sides are found, the stub parameter list is filtered by version
 *    (PhpStormStubsElementAvailable `from`/`to` on parameters →
 *    sinceVersion/removedVersion) and the resulting count is compared with
 *    the reflection count.
 *
 * Parameter version filtering uses inclusive boundaries for removedVersion (`<=`),
 * consistent with how PhpStormStubsElementAvailable `to` is interpreted elsewhere
 * (e.g. `to: '7.0'` means the parameter is still available in PHP 7.0).
 *
 * Known problems are supported:
 * - EntityType::FUNCTION + functionId + 'ParametersCountCheck'
 *   → skips the parameter-count check for that specific function.
 */
class FunctionParametersCountCheck extends AbstractCallableCheck
{
    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, EntityType::FUNCTION->value, $entityId, 'ParametersCountCheck', $phpVersion)) {
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

        $reflCount = count($reflFunction->getParameters());

        // Count available parameters, deduplicating by name.
        // When a version-bounded placeholder and a variadic share the same name
        // (e.g. `#[PhpStormStubsElementAvailable(to:'7.4')] $vars` + `mixed ...$vars`),
        // they represent one mandatory variadic parameter and must be counted as one.
        $availableParamNames = [];
        foreach ($stubFunction->getParameters() as $param) {
            $sinceVersion   = $param->getSinceVersion();
            $removedVersion = $param->getRemovedVersion();

            $available = ($sinceVersion === null || version_compare($phpVersion, $sinceVersion, '>='))
                && ($removedVersion === null || version_compare($phpVersion, $removedVersion, '<'));

            if ($available) {
                $availableParamNames[$param->getName()] = true;
            }
        }
        $stubCount = count($availableParamNames);

        if ($reflCount !== $stubCount) {
            $results->addFailure(
                $entityId,
                "Function {$entityId} has {$reflCount} parameter(s) in PHP {$phpVersion} but {$stubCount} in stubs"
            );
            return $results;
        }

        $results->addSuccess($entityId);
        return $results;
    }
}
