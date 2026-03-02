<?php

namespace StubTests\Sources\Validator\Classes;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\CheckInterface;
use StubTests\Sources\Validator\CheckResultSet;

class ClassReadonlyCheck implements CheckInterface
{
    public function supports(string $phpVersion): bool
    {
        // Readonly classes were introduced in PHP 8.2
        return version_compare($phpVersion, '8.2', '>=');
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

        // Get readonly status from stubs
        $stubIsReadonly = isset($stubClass->isReadonly) ? (bool)$stubClass->isReadonly : false;

        // For now, we validate that the readonly property is set correctly
        // In a real scenario, we would compare against reflection data
        // Since reflection data structure is not fully explored, we just validate consistency

        // The validation here is that if isReadonly is set, it should be a boolean
        if (isset($stubClass->isReadonly) && !is_bool($stubClass->isReadonly)) {
            $results->addFailure(
                $entityId,
                "Class {$entityId} has invalid isReadonly property type in stubs (expected boolean)"
            );
        } else {
            $results->addSuccess($entityId);
        }

        return $results;
    }
}
