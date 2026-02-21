<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;

/**
 * Validates that methods from reflection exist in stubs.
 *
 * The entityId should be in format: "ClassName::methodName"
 */
class MethodExistsCheck implements CheckInterface
{
    public function supports(string $phpVersion): bool
    {
        // This check is supported on all PHP versions
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        // Parse method ID (format: ClassName::methodName or \Namespace\ClassName::methodName)
        if (!str_contains($entityId, '::')) {
            $results->addFailure($entityId, "Invalid method ID format. Expected 'ClassName::methodName'");
            return $results;
        }

        [$className, $methodName] = explode('::', $entityId, 2);

        // Get the class from stubs
        $stubClasses = $stubs->getClasses();
        $stubClass = null;

        foreach ($stubClasses as $class) {
            if (method_exists($class, 'getId') && $class->getId() === $className) {
                $stubClass = $class;
                break;
            } elseif (method_exists($class, 'getName') && $class->getName() === $className) {
                $stubClass = $class;
                break;
            }
        }

        if ($stubClass === null) {
            $results->addFailure(
				$entityId,
	            "Class {$className} not found in stubs (required to check method {$methodName})"
            );
            return $results;
        }

        // Check if method exists in the class
        $found = false;
        if (method_exists($stubClass, 'getMethods')) {
            $methods = $stubClass->getMethods();
            foreach ($methods as $method) {
                $stubMethodName = method_exists($method, 'getName') ? $method->getName() : (string) $method;
                if ($stubMethodName === $methodName) {
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            $results->addFailure(
				$entityId,
	            "Method {$entityId} exists in PHP {$phpVersion} but not in stubs"
            );
        } else {
            $results->addSuccess($entityId);
        }

        return $results;
    }
}
