<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;

/**
 * Validates that return types in stubs match those in reflection.
 *
 * The entityId should be in format: "FunctionName" or "ClassName::methodName"
 */
class ReturnTypesCheck implements CheckInterface
{
    public function supports(string $phpVersion): bool
    {
        // Return type declarations were introduced in PHP 7.0
        return version_compare($phpVersion, '7.0', '>=');
    }

    public function run(ParsedDataStorageManager $stubs, string $functionOrMethodId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        // Get the function/method from reflection
        $reflection = \StubTests\Sources\Runner\Runner::getReflection($phpVersion);
        $reflectionCallable = $this->findCallable($reflection, $functionOrMethodId);

        if ($reflectionCallable === null) {
            $results->addFailure(
                $functionOrMethodId,
                "Function/method {$functionOrMethodId} not found in reflection data"
            );
            return $results;
        }

        // Get the function/method from stubs
        $stubCallable = $this->findCallable($stubs, $functionOrMethodId);

        if ($stubCallable === null) {
            $results->addFailure(
                $functionOrMethodId,
                "Function/method {$functionOrMethodId} not found in stubs"
            );
            return $results;
        }

        // Get return types from both
        $reflectionReturnType = $this->getReturnTypeString($reflectionCallable);
        $stubReturnType = $this->getReturnTypeString($stubCallable);

        // Compare return types
        if ($reflectionReturnType !== $stubReturnType) {
            $results->addFailure(
                $functionOrMethodId,
                "Return type mismatch: reflection has '{$reflectionReturnType}', " .
                "stubs have '{$stubReturnType}'"
            );
        } else {
            $results->addSuccess($functionOrMethodId);
        }

        return $results;
    }

    /**
     * Get the return type string representation from a function/method.
     *
     * @param mixed $callable
     * @return string
     */
    private function getReturnTypeString($callable): string
    {
        // Try to get return type from signature
        if (method_exists($callable, 'getReturnTypeFromSignature')) {
            $returnType = $callable->getReturnTypeFromSignature();

            if ($returnType !== null) {
                if (is_object($returnType)) {
                    if (method_exists($returnType, '__toString')) {
                        return (string) $returnType;
                    }
                    if (method_exists($returnType, 'getTypeName')) {
                        return $returnType->getTypeName();
                    }
                }
                return (string) $returnType;
            }
        }

        // Try alternative methods
        if (method_exists($callable, 'getReturnType')) {
            $returnType = $callable->getReturnType();

            if ($returnType !== null) {
                if (is_object($returnType)) {
                    if (method_exists($returnType, '__toString')) {
                        return (string) $returnType;
                    }
                    if (method_exists($returnType, 'getTypeName')) {
                        return $returnType->getTypeName();
                    }
                }
                return (string) $returnType;
            }
        }

        return 'mixed'; // No return type specified
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
