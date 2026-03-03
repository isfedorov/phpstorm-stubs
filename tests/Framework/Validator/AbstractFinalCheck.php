<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Base for entity-level final-flag checks.
 *
 * Compares the `isFinal` property of an entity in stubs against its reflection
 * counterpart and reports a failure on any mismatch.
 *
 * Subclasses override the template methods to target a different entity type
 * (e.g. enums); the defaults work for classes.
 */
abstract class AbstractFinalCheck extends AbstractClassCheck
{
    public function supports(string $phpVersion): bool
    {
        return true;
    }

    /**
     * Template method: look up the entity in storage by ID.
     * Default targets classes; override for enums/interfaces.
     */
    protected function findEntity(ParsedDataStorageManager $storage, string $entityId): mixed
    {
        return $this->findClassById($storage, $entityId);
    }

    /**
     * Template method: human-readable label used in failure messages.
     * Default is 'Class'; override for enums/interfaces.
     */
    protected function getEntityLabel(): string
    {
        return 'Class';
    }

    /**
     * Template method: entity type for known-problem lookups.
     * Default is CLASS_TYPE; override for enums/interfaces.
     */
    protected function getEntityType(): string
    {
        return EntityType::CLASS_TYPE->value;
    }

    /**
     * Template method: check name for known-problem lookups.
     */
    protected function getCheckName(): string
    {
        return 'ClassFinalCheck';
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, $this->getEntityType(), $entityId, $this->getCheckName(), $phpVersion)) {
            return $results;
        }

        $label      = $this->getEntityLabel();
        $reflection = $this->reflectionProvider->getReflection($phpVersion);

        $reflEntity = $this->findEntity($reflection, $entityId);
        if ($reflEntity === null) {
            $results->addFailure($entityId, "{$label} {$entityId} not found in reflection data");
            return $results;
        }

        $stubEntity = $this->findEntity($stubs, $entityId);
        if ($stubEntity === null) {
            $results->addFailure($entityId, "{$label} {$entityId} not found in stubs");
            return $results;
        }

        $reflIsFinal = (bool)($reflEntity->isFinal ?? false);
        $stubIsFinal = (bool)($stubEntity->isFinal ?? false);

        if ($reflIsFinal === $stubIsFinal) {
            $results->addSuccess($entityId);
        } else {
            $expected = $reflIsFinal ? 'final' : 'non-final';
            $actual   = $stubIsFinal ? 'final' : 'non-final';
            $results->addFailure(
                $entityId,
                "{$label} {$entityId} is {$expected} in PHP {$phpVersion} but {$actual} in stubs"
            );
        }

        return $results;
    }
}
