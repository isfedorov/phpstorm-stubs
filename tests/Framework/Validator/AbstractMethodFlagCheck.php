<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Base class for checks that compare a boolean method flag (e.g. isFinal, isStatic)
 * between reflection and stubs.
 *
 * Subclasses must implement:
 * - getCheckName(): the name used for known-problem lookups
 * - describeMismatch(): returns a failure message when the flags differ, or null when they match
 */
abstract class AbstractMethodFlagCheck extends AbstractClassCheck
{
    abstract protected function getCheckName(): string;

    /**
     * Compare a flag on the reflection and stub method.
     * Return a descriptive failure message if there is a mismatch, or null if they match.
     *
     * @param mixed $reflMethod reflection method object
     */
    abstract protected function describeMismatch(
        string $methodEntityId,
        mixed $reflMethod,
        PHPMethod $stubMethod,
        string $phpVersion
    ): ?string;

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
     * @return array<string, PHPMethod>
     */
    protected function collectEntityStubMethods(mixed $entity, string $phpVersion): array
    {
        return $this->collectVersionedStubMethods($entity, $phpVersion);
    }

    /**
     * Template method: label used in "not found" error messages.
     * Override to return "Interface" in interface-specific subclasses.
     */
    protected function getEntityLabel(): string
    {
        return 'Class';
    }

    /**
     * Template method: entity type for known-problem lookups.
     * Override to return EntityType::INTERFACE_TYPE in interface subclasses.
     */
    protected function getEntityType(): string
    {
        return EntityType::CLASS_TYPE->value;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, $this->getEntityType(), $entityId, $this->getCheckName(), $phpVersion)) {
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

        $stubMethodMap = $this->collectEntityStubMethods($stubClass, $phpVersion);

        $hasMismatch = false;
        foreach ($reflectionClass->getMethods() as $reflMethod) {
            $name = $reflMethod->getName();
            if ($name === null || !isset($stubMethodMap[$name])) {
                // Null name or method absent from stubs — ClassMethodsExistCheck's responsibility
                continue;
            }

            $methodEntityId = $entityId . '::' . $name;
            $mismatchMessage = $this->describeMismatch($methodEntityId, $reflMethod, $stubMethodMap[$name], $phpVersion);

            if ($mismatchMessage === null) {
                continue;
            }

            $hasMismatch = true;
            if (!$this->skipWithKnownProblem($results, EntityType::METHOD->value, $methodEntityId, $this->getCheckName(), $phpVersion)) {
                $results->addFailure($methodEntityId, $mismatchMessage);
            }
        }

        if (!$hasMismatch) {
            $results->addSuccess($entityId);
        }

        return $results;
    }

    /**
     * Filter parameters by version availability, then deduplicate consecutive same-named
     * parameters where the second is variadic (the stub workaround for non-optional variadics).
     *
     * @param  PHPParameter[] $params
     * @return PHPParameter[]
     */
    protected function filterAndDeduplicateParams(array $params, string $phpVersion): array
    {
        $filtered = [];
        foreach ($params as $param) {
            $since   = $param->getSinceVersion();
            $removed = $param->getRemovedVersion();

            $available = ($since === null || version_compare($phpVersion, $since, '>='))
                && ($removed === null || version_compare($phpVersion, $removed, '<='));

            if ($available) {
                $filtered[] = $param;
            }
        }

        // Merge consecutive same-named params where the second is variadic
        $merged = [];
        $count  = count($filtered);
        for ($i = 0; $i < $count; $i++) {
            $current = $filtered[$i];
            $next    = $filtered[$i + 1] ?? null;

            if ($next !== null
                && $current->getName() === $next->getName()
                && $next->isVariadic()
            ) {
                $merged[] = $next;
                $i++;
            } else {
                $merged[] = $current;
            }
        }

        return $merged;
    }
}
