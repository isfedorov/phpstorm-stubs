<?php

namespace StubTests\Sources\Validator\Classes;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\CheckInterface;
use StubTests\Sources\Validator\CheckResultSet;

class ClassNamespaceCheck implements CheckInterface
{
    public function supports(string $phpVersion): bool
    {
        // Namespace validation is supported on all PHP versions
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        // Get class from stubs
        $stubClasses = $stubs->getClasses();
        $stubClass = null;
        foreach ($stubClasses as $class) {
            if ($class->getId() === $entityId) {
                $stubClass = $class;
                break;
            }
        }

        if ($stubClass === null) {
            $results->addFailure($entityId, "Class {$entityId} not found in stubs");
            return $results;
        }

        // Get namespace from stubs
        $stubNamespace = method_exists($stubClass, 'getNamespace') ? $stubClass->getNamespace() : null;

        // For validation, we expect the namespace to match the class ID structure
        // Extract namespace from class ID (format: \Namespace\ClassName, \ClassName, or ClassName)
        $lastBackslashPos = strrpos($entityId, '\\');
        if ($lastBackslashPos === false) {
            // No backslash found - class without namespace (edge case)
            $expectedNamespace = null;
        } elseif ($lastBackslashPos === 0) {
            // Class ID starts with backslash (e.g., '\stdClass') - root namespace
            // In the framework, root namespace is represented as '\' (backslash)
            $expectedNamespace = '\\';
        } else {
            // Class ID has namespace (e.g., '\Foo\Bar\Class')
            $expectedNamespace = substr($entityId, 0, $lastBackslashPos);
        }

        // Compare namespaces
        if ($stubNamespace !== $expectedNamespace) {
            $results->addFailure(
                $entityId,
                "Namespace mismatch for class {$entityId}: expected '" .
                ($expectedNamespace ?? '(no namespace)') .
                "', found '" .
                ($stubNamespace ?? '(no namespace)') .
                "' in stubs"
            );
        } else {
            $results->addSuccess($entityId);
        }

        return $results;
    }
}
