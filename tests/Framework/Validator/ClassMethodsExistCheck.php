<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that all methods present in reflection also exist in stubs.
 *
 * The check is performed per-class: for each class entity ID the validator
 * 1. collects all methods from the reflection class (including private),
 * 2. collects all version-appropriate methods from the stub class and its full
 *    ancestor chain in stubs,
 * 3. reports any reflection method that is absent from the stub method set.
 *
 * Version filtering for stub methods uses sinceVersion/removedVersion stored on
 * each PHPMethod (populated from @since/@removed tags and PhpStormStubsElementAvailable
 * attributes during stub parsing). A stub method is considered available for a given
 * PHP version if:
 *   - sinceVersion is null OR phpVersion >= sinceVersion
 *   - AND removedVersion is null OR phpVersion <= removedVersion
 *
 * Known problems are supported at two granularities:
 * - class-level: EntityType::CLASS_TYPE + classId + 'ClassMethodsExistCheck'
 *   → skips all method checks for the class.
 * - method-level: EntityType::METHOD + '\ClassName::methodName' + 'ClassMethodsExistCheck'
 *   → skips only that specific missing-method failure.
 */
class ClassMethodsExistCheck extends AbstractClassCheck
{
    public function supports(string $phpVersion): bool
    {
        return true;
    }

    /**
     * Template method: look up the entity in the given storage by ID.
     * Override in subclasses to support interfaces instead of classes.
     */
    protected function findEntityById(ParsedDataStorageManager $storage, string $entityId): mixed
    {
        return $this->findClassById($storage, $entityId);
    }

    /**
     * Template method: collect version-available stub methods for the entity.
     * Override in subclasses to traverse interface hierarchies instead of class hierarchies.
     *
     * @return array<string, mixed>
     */
    protected function collectEntityStubMethods(mixed $entity, string $phpVersion): array
    {
        return $this->collectVersionedStubMethods($entity, $phpVersion);
    }

    /**
     * Template method: label used in "not found" error messages.
     */
    protected function getEntityLabel(): string
    {
        return 'Class';
    }

    /**
     * Template method: entity type for known-problem lookups.
     */
    protected function getEntityType(): string
    {
        return EntityType::CLASS_TYPE->value;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        // Entity-level known problem skips all method validation for this entity
        if ($this->skipWithKnownProblem($results, $this->getEntityType(), $entityId, 'ClassMethodsExistCheck', $phpVersion)) {
            return $results;
        }

        $reflection = $this->reflectionProvider->getReflection($phpVersion);
        $label      = $this->getEntityLabel();

        $reflectionClass = $this->findEntityById($reflection, $entityId);
        if ($reflectionClass === null) {
            $results->addFailure($entityId, "{$label} {$entityId} not found in reflection data");
            return $results;
        }

        $stubClass = $this->findEntityById($stubs, $entityId);
        if ($stubClass === null) {
            $results->addFailure($entityId, "{$label} {$entityId} not found in stubs");
            return $results;
        }

        // Collect all method names from reflection (including private).
        // ReflectionClass::getMethods() returns own methods (all visibility) plus
        // inherited public/protected methods. Private methods from parent classes are
        // NOT included by PHP's reflection, only own private methods appear.
        $reflectionMethodNames = [];
        foreach ($reflectionClass->getMethods() as $method) {
            $name = $method->getName();
            if ($name !== null) {
                $reflectionMethodNames[$name] = true;
            }
        }

        // Collect all method names from the stub entity and its full hierarchy,
        // filtered to only those available in the given PHP version.
        $stubMethodNames = array_keys($this->collectEntityStubMethods($stubClass, $phpVersion));

        $missingMethods = array_diff(array_keys($reflectionMethodNames), $stubMethodNames);

        if (empty($missingMethods)) {
            $results->addSuccess($entityId);
            return $results;
        }

        // For each missing method, check for a method-level known problem entry.
        sort($missingMethods);
        foreach ($missingMethods as $methodName) {
            $methodEntityId = $entityId . '::' . $methodName;

            if (!$this->skipWithKnownProblem($results, EntityType::METHOD->value, $methodEntityId, 'ClassMethodsExistCheck', $phpVersion)) {
                $results->addFailure(
                    $methodEntityId,
                    "Method {$methodEntityId} exists in PHP {$phpVersion} but not in stubs"
                );
            }
        }

        return $results;
    }
}
