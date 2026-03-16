<?php

namespace StubTests\Sources\Validator\Classes;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractClassCheck;
use StubTests\Sources\Validator\CheckInterface;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that the `readonly` modifier on a class in stubs matches reflection.
 */
class ClassReadonlyCheck extends AbstractClassCheck implements CheckInterface
{
    public function supports(string $phpVersion): bool
    {
        // Readonly classes were introduced in PHP 8.2
        return version_compare($phpVersion, '8.2', '>=');
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, EntityType::CLASS_TYPE->value, $entityId, 'ClassReadonlyCheck', $phpVersion)) {
            return $results;
        }

        $reflection = $this->reflectionProvider->getReflection($phpVersion);

        $reflClass = $this->findClassById($reflection, $entityId);
        if ($reflClass === null) {
            $results->addFailure($entityId, "Class {$entityId} not found in reflection data");
            return $results;
        }

        $stubClass = $this->findClassById($stubs, $entityId);
        if ($stubClass === null) {
            $results->addFailure($entityId, "Class {$entityId} not found in stubs");
            return $results;
        }

        $reflIsReadonly = (bool)($reflClass->isReadonly ?? false);
        $stubIsReadonly = (bool)($stubClass->isReadonly ?? false);

        if ($reflIsReadonly === $stubIsReadonly) {
            $results->addSuccess($entityId);
        } else {
            $expected = $reflIsReadonly ? 'readonly' : 'non-readonly';
            $actual   = $stubIsReadonly ? 'readonly' : 'non-readonly';
            $results->addFailure(
                $entityId,
                "Class {$entityId} is {$expected} in PHP {$phpVersion} but {$actual} in stubs"
            );
        }

        return $results;
    }
}
