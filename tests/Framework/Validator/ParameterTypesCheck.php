<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;

/**
 * Validates that parameter types in stubs match those in reflection.
 *
 * The entityId should be in format: "FunctionName" or "ClassName::methodName"
 */
class ParameterTypesCheck implements CheckInterface
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

        // Get the function/method from reflection
        $reflection = \StubTests\Sources\Runner\Runner::getReflection($phpVersion);
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
                $results->addFailure(
					$entityId,
	                "Parameter #{$index} missing in stubs"
                );
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
            if (method_exists($type, 'getTypeName')) {
                return $type->getTypeName();
            }
        }

        return (string) $type;
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
