<?php

namespace StubTests\Sources\Validator\Functions;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractCallableCheck;
use StubTests\Sources\Validator\CheckResultSet;

/**
 * Validates that parameter names in stubs match those in reflection.
 *
 * This check is only relevant for PHP >= 8.0 where named parameters were introduced.
 * The entityId should be in format: "FunctionName" or "ClassName::methodName"
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

        // Check if this entity has a known problem that should skip validation
        $entityType = str_contains($entityId, '::') ? 'methods' : 'functions';
        if ($this->skipWithKnownProblem($results, $entityType, $entityId, 'ParameterNamesCheck', $phpVersion)) {
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

        // Compare parameter names
        $reflectionParamNames = [];
        foreach ($reflectionParams as $param) {
            if (method_exists($param, 'getName')) {
                $reflectionParamNames[] = $param->getName();
            }
        }

        $stubParamNames = [];
        foreach ($stubParams as $param) {
            if (method_exists($param, 'getName')) {
                $stubParamNames[] = $param->getName();
            }
        }

        // Check parameter count matches
        if (count($reflectionParamNames) !== count($stubParamNames)) {
            $results->addFailure(
                $entityId,
                "Parameter count mismatch: reflection has " . count($reflectionParamNames) .
                " parameters, stubs have " . count($stubParamNames) . " parameters"
            );
            return $results;
        }

        // Check each parameter name matches
        foreach ($reflectionParamNames as $index => $reflectionName) {
            $stubName = $stubParamNames[$index] ?? null;

            if ($reflectionName !== $stubName) {
                $results->addFailure(
                    $entityId,
                    "Parameter #{$index} name mismatch: reflection has '{$reflectionName}', " .
                    "stubs have '{$stubName}'"
                );
            }
        }

        if (!$results->hasFailures()) {
            $results->addSuccess($entityId);
        }

        return $results;
    }

    /**
     * Filter parameters by their version availability.
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
            $isNotRemoved     = $removedVersion === null || version_compare($phpVersion, $removedVersion, '<=');

            if ($isAvailableSince && $isNotRemoved) {
                $filtered[] = $param;
            }
        }

        return $filtered;
    }
}
