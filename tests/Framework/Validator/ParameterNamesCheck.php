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
    public function supports(string $phpVersion): bool
    {
        // Named parameters were introduced in PHP 8.0
        return version_compare($phpVersion, '8.0', '>=');
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

        // Get parameters from both
        $reflectionParams = method_exists($reflectionCallable, 'getParameters')
            ? $reflectionCallable->getParameters()
            : [];
        $stubParams = method_exists($stubCallable, 'getParameters')
            ? $stubCallable->getParameters()
            : [];

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
                $functionOrMethodId,
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
                    $functionOrMethodId,
                    "Parameter #{$index} name mismatch: reflection has '{$reflectionName}', " .
                    "stubs have '{$stubName}'"
                );
            }
        }

        if (!$results->hasFailures()) {
            $results->addSuccess($functionOrMethodId);
        }

        return $results;
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
