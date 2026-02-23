<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;

/**
 * Validates that parameter names in stubs match those in reflection.
 *
 * This check is only relevant for PHP >= 8.0 where named parameters were introduced.
 * The entityId should be in format: "FunctionName" or "ClassName::methodName"
 */
class ParameterNamesCheck implements CheckInterface
{
    private ReflectionProviderInterface $reflectionProvider;
    private KnownProblemsRegistry $knownProblemsRegistry;

    /**
     * @param ReflectionProviderInterface|null $reflectionProvider Optional reflection provider for dependency injection.
     *                                                              Defaults to RunnerReflectionProvider for production use.
     * @param KnownProblemsRegistry|null $knownProblemsRegistry Optional registry for known problems.
     *                                                            Defaults to singleton instance.
     */
    public function __construct(
        ?ReflectionProviderInterface $reflectionProvider = null,
        ?KnownProblemsRegistry $knownProblemsRegistry = null
    ) {
        $this->reflectionProvider = $reflectionProvider ?? new RunnerReflectionProvider();
        $this->knownProblemsRegistry = $knownProblemsRegistry ?? KnownProblemsRegistry::getInstance();
    }

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
        if ($this->knownProblemsRegistry->shouldSkipValidation(
            $entityType,
            $entityId,
            'ParameterNamesCheck',
            $phpVersion
        )) {
            $reason = $this->knownProblemsRegistry->getSkipReason(
                $entityType,
                $entityId,
                'ParameterNamesCheck',
                $phpVersion
            );
            // Mark as success with note that validation was skipped
            $results->addSuccess($entityId . ' (skipped: ' . $reason . ')');
            return $results;
        }

        // Get the function/method from reflection
        $reflection = $this->reflectionProvider->getReflection($phpVersion);
        $reflectionCallable = $this->findCallable($reflection, $entityId);

        if ($reflectionCallable === null) {
            $results->addFailure(
				$entityId,
	            "Function/method {$entityId} not found in reflection data"
            );
            return $results;
        }

        // Get the function/method from stubs
        $stubCallable = $this->findCallable($stubs, $entityId);

        if ($stubCallable === null) {
            $results->addFailure(
				$entityId,
	            "Function/method {$entityId} not found in stubs"
            );
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
     * Parameters with PhpStormStubsElementAvailable attributes may have sinceVersion and removedVersion.
     * This method filters out parameters that are not available in the target PHP version.
     *
     * @param array $parameters Array of parameter objects
     * @param string $phpVersion Target PHP version (e.g., '8.0', '8.1')
     * @return array Filtered array of parameters available in the target version
     */
    private function filterParametersByVersion(array $parameters, string $phpVersion): array
    {
        $filtered = [];

        foreach ($parameters as $param) {
            // Check if parameter has version constraints
            $sinceVersion = method_exists($param, 'getSinceVersion') ? $param->getSinceVersion() : null;
            $removedVersion = method_exists($param, 'getRemovedVersion') ? $param->getRemovedVersion() : null;

            // Parameter is available if:
            // - sinceVersion is null OR target version >= sinceVersion
            // - AND removedVersion is null OR target version <= removedVersion
            $isAvailableSince = $sinceVersion === null || version_compare($phpVersion, $sinceVersion, '>=');
            $isNotRemoved = $removedVersion === null || version_compare($phpVersion, $removedVersion, '<=');

            if ($isAvailableSince && $isNotRemoved) {
                $filtered[] = $param;
            }
        }

        return $filtered;
    }

    /**
     * Find a function or method in the given storage.
     *
     * @param ParsedDataStorageManager $storage
     * @param string $functionOrMethodId Format: "functionName" or "ClassName::methodName"
     * @return mixed|null The function/method object or null if not found
     */
    private function findCallable(ParsedDataStorageManager $storage, string $functionOrMethodId)
    {
        // Check if it's a method (contains ::)
        if (str_contains($functionOrMethodId, '::')) {
            [$className, $methodName] = explode('::', $functionOrMethodId, 2);

            // Find the class
            $classes = $storage->getClasses();
            foreach ($classes as $class) {
                $classId = method_exists($class, 'getId') ? $class->getId() :
                    (method_exists($class, 'getName') ? $class->getName() : '');

                if ($classId === $className) {
                    // Find the method in this class
                    if (method_exists($class, 'getMethods')) {
                        $methods = $class->getMethods();
                        foreach ($methods as $method) {
                            $stubMethodName = method_exists($method, 'getName') ? $method->getName() : '';
                            if ($stubMethodName === $methodName) {
                                return $method;
                            }
                        }
                    }
                }
            }
            return null;
        }

        // It's a function
        $functions = $storage->getFunctions();
        foreach ($functions as $function) {
            $functionId = method_exists($function, 'getId') ? $function->getId() :
                (method_exists($function, 'getName') ? $function->getName() : '');

            if ($functionId === $functionOrMethodId) {
                return $function;
            }
        }

        return null;
    }
}
