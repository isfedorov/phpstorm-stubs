<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that the visibility (public/protected/private) of properties in stubs matches reflection.
 *
 * For each class identified by $entityId the validator:
 * 1. Iterates all properties reported by reflection for the class.
 * 2. Looks up each property in the version-filtered stub hierarchy (parent classes),
 *    collecting a name → PHPProperty map with child-wins-over-parent priority.
 * 3. If the stub property is not found it is silently skipped — existence is
 *    ClassPropertiesExistCheck's responsibility.
 * 4. When both sides are found, their visibility is compared and any mismatch
 *    is reported as a failure.
 *
 * Known problems are supported at two granularities:
 * - class-level: EntityType::CLASS_TYPE + classId + 'ClassPropertiesVisibilityCheck'
 *   → skips all visibility checks for the class.
 * - property-level: EntityType::PROPERTY + '\ClassName::$propertyName' + 'ClassPropertiesVisibilityCheck'
 *   → skips only that specific mismatch.
 */
class ClassPropertiesVisibilityCheck extends AbstractClassCheck
{
    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, EntityType::CLASS_TYPE->value, $entityId, 'ClassPropertiesVisibilityCheck', $phpVersion)) {
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

        $stubProperties = $this->collectVersionedStubPropertiesMap($stubClass, $phpVersion);

        $hasMismatch = false;
        foreach ($reflectionClass->getProperties() as $reflProperty) {
            $name = $reflProperty->getName();
            if ($name === null || !isset($stubProperties[$name])) {
                // Property absent from stubs — ClassPropertiesExistCheck's responsibility
                continue;
            }

            $propertyEntityId = $entityId . '::$' . $name;
            $stubProperty = $stubProperties[$name];

            $reflVisibility = $reflProperty->getAccess()?->toString() ?? 'public';
            $stubVisibility = $stubProperty->getAccess()?->toString() ?? 'public';

            if ($reflVisibility === $stubVisibility) {
                continue;
            }

            $hasMismatch = true;
            if (!$this->skipWithKnownProblem($results, EntityType::PROPERTY->value, $propertyEntityId, 'ClassPropertiesVisibilityCheck', $phpVersion)) {
                $results->addFailure(
                    $propertyEntityId,
                    "Property {$propertyEntityId} is {$reflVisibility} in PHP {$phpVersion} but {$stubVisibility} in stubs"
                );
            }
        }

        if (!$hasMismatch) {
            $results->addSuccess($entityId);
        }

        return $results;
    }

    /**
     * Collect version-filtered stub properties from the full parent class chain.
     * Returns a map of property name → PHPProperty. Child definitions win over parent.
     *
     * A property is considered available if:
     * - sinceVersion is null OR phpVersion >= sinceVersion
     * - AND removedVersion is null OR phpVersion <= removedVersion
     *
     * @return array<string, PHPProperty>
     */
    private function collectVersionedStubPropertiesMap(PHPClass $class, string $phpVersion): array
    {
        $propertyMap = [];
        $visited     = [];

        $current = $class;
        while ($current !== null) {
            $id = $current->getId();
            if ($id !== null && in_array($id, $visited, true)) {
                break; // cycle guard
            }
            if ($id !== null) {
                $visited[] = $id;
            }

            foreach ($current->getProperties() as $property) {
                $name = $property->getName();
                if ($name === null || isset($propertyMap[$name])) {
                    continue;
                }

                $sinceVersion   = $property->getSinceVersion();
                $removedVersion = $property->getRemovedVersion();

                $available = ($sinceVersion === null || version_compare($phpVersion, $sinceVersion, '>='))
                    && ($removedVersion === null || version_compare($phpVersion, $removedVersion, '<='));

                if ($available) {
                    $propertyMap[$name] = $property;
                }
            }

            $current = $current->parentClass;
        }

        return $propertyMap;
    }
}
