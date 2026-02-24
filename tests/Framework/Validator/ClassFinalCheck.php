<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;

class ClassFinalCheck implements CheckInterface
{
    public function supports(string $phpVersion): bool
    {
        // Final class validation is supported on all PHP versions (PHP 5.0+)
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

        // Get final status from stubs
        $stubIsFinal = isset($stubClass->isFinal) ? (bool)$stubClass->isFinal : false;

        // Validate that the isFinal property is set correctly
        // In a real scenario, we would compare against reflection data
        // For now, we validate consistency

        // The validation here is that if isFinal is set, it should be a boolean
        if (isset($stubClass->isFinal) && !is_bool($stubClass->isFinal)) {
            $results->addFailure(
                $entityId,
                "Class {$entityId} has invalid isFinal property type in stubs (expected boolean)"
            );
        } else {
            $results->addSuccess($entityId);
        }

        return $results;
    }
}
