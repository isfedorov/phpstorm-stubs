<?php

namespace StubTests\Sources\Validator\Functions;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractCallableCheck;
use StubTests\Sources\Validator\CheckResultSet;

/**
 * Validates that parameter types in stubs match those in reflection.
 *
 * The entityId should be in format: "FunctionName" or "ClassName::methodName"
 */
class ParameterTypesCheck extends AbstractCallableCheck
{
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

        // Compare parameter types
        foreach ($reflectionParams as $index => $reflectionParam) {
            $stubParam = $stubParams[$index] ?? null;

            if ($stubParam === null) {
                $results->addFailure($entityId, "Parameter #{$index} missing in stubs");
                continue;
            }

            $reflectionType = $this->getParameterTypeString($reflectionParam);
            $stubType = $this->getParameterTypeString($stubParam);

            if ($reflectionType !== $stubType) {
                $paramName = method_exists($reflectionParam, 'getName') ? $reflectionParam->getName() : "#{$index}";
                $results->addFailure(
                    $entityId,
                    "Parameter '{$paramName}' type mismatch: reflection has '{$reflectionType}', " .
                    "stubs have '{$stubType}'"
                );
            }
        }

        if (!$results->hasFailures()) {
            $results->addSuccess($entityId);
        }

        return $results;
    }

    /**
     * Get the type string representation from a parameter.
     *
     * @param mixed $param
     * @return string
     */
    private function getParameterTypeString($param): string
    {
        if (!method_exists($param, 'getType')) {
            return 'mixed'; // No type information
        }

        $type = $param->getType();

        if ($type === null) {
            return 'mixed';
        }

        // Handle different type objects
        if (is_object($type)) {
            if (method_exists($type, '__toString')) {
                return (string) $type;
            }
            if (method_exists($type, 'toString')) {
                $typeString = $type->toString();
                // NoType returns empty string, which should be treated as 'mixed'
                return $typeString === '' ? 'mixed' : $typeString;
            }
            if (method_exists($type, 'getTypeName')) {
                return $type->getTypeName();
            }
        }

        return (string) $type;
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
