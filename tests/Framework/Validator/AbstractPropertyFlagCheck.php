<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Base class for checks that compare a boolean property flag (e.g. isStatic, visibility)
 * between reflection and stubs.
 *
 * Subclasses must implement:
 * - getCheckName(): the name used for known-problem lookups
 * - describeMismatch(): returns a failure message when the flags differ, or null when they match
 */
abstract class AbstractPropertyFlagCheck extends AbstractClassCheck
{
    abstract protected function getCheckName(): string;

    /**
     * Compare a flag on the reflection and stub property.
     * Return a descriptive failure message if there is a mismatch, or null if they match.
     */
    abstract protected function describeMismatch(
        string $propertyEntityId,
        PHPProperty $reflProperty,
        PHPProperty $stubProperty,
        string $phpVersion
    ): ?string;

    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, EntityType::CLASS_TYPE->value, $entityId, $this->getCheckName(), $phpVersion)) {
            return $results;
        }

        $reflection = $this->reflectionProvider->getReflection($phpVersion);

        $reflectionClass = $this->findClassById($reflection, $entityId);
        if ($reflectionClass === null) {
            $results->addFailure($entityId, "Class {$entityId} not found in reflection data");
            return $results;
        }

        $stubClass = $this->findClassById($stubs, $entityId);
        if ($stubClass === null) {
            $results->addFailure($entityId, "Class {$entityId} not found in stubs");
            return $results;
        }

        $stubPropertyMap = $this->collectVersionedStubPropertiesMap($stubClass, $phpVersion);

        $hasMismatch = false;
        foreach ($reflectionClass->getProperties() as $reflProperty) {
            $name = $reflProperty->getName();
            if ($name === null || !isset($stubPropertyMap[$name])) {
                // Null name or property absent from stubs — ClassPropertiesExistCheck's responsibility
                continue;
            }

            $propertyEntityId = $entityId . '::$' . $name;
            $mismatchMessage  = $this->describeMismatch($propertyEntityId, $reflProperty, $stubPropertyMap[$name], $phpVersion);

            if ($mismatchMessage === null) {
                continue;
            }

            $hasMismatch = true;
            if (!$this->skipWithKnownProblem($results, EntityType::PROPERTY->value, $propertyEntityId, $this->getCheckName(), $phpVersion)) {
                $results->addFailure($propertyEntityId, $mismatchMessage);
            }
        }

        if (!$hasMismatch) {
            $results->addSuccess($entityId);
        }

        return $results;
    }
}
