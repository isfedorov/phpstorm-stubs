<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClassLikeObject;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that the `final` modifier on methods in stubs matches reflection.
 *
 * For each class identified by $entityId the validator:
 * 1. Iterates all methods reported by reflection for the class.
 * 2. Looks up each method in the version-filtered stub hierarchy (parent classes
 *    and interfaces), stripping PS_UNRESERVE_PREFIX_ where needed.
 * 3. If the stub method is not found it is silently skipped — existence is
 *    ClassMethodsExistCheck's responsibility.
 * 4. When both sides are found, their isFinal flags are compared and any
 *    mismatch is reported as a failure.
 *
 * Known problems are supported at two granularities:
 * - class-level: EntityType::CLASS_TYPE + classId + 'ClassFinalMethodsCheck'
 *   → skips all final-method checks for the class.
 * - method-level: EntityType::METHOD + '\ClassName::methodName' + 'ClassFinalMethodsCheck'
 *   → skips only that specific mismatch.
 */
class ClassFinalMethodsCheck implements CheckInterface
{
    private ReflectionProviderInterface $reflectionProvider;
    private KnownProblemsRegistry $knownProblemsRegistry;

    public function __construct(
        ?ReflectionProviderInterface $reflectionProvider = null,
        ?KnownProblemsRegistry $knownProblemsRegistry = null
    ) {
        $this->reflectionProvider = $reflectionProvider ?? new RunnerReflectionProvider();
        $this->knownProblemsRegistry = $knownProblemsRegistry ?? KnownProblemsRegistry::getInstance();
    }

    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->knownProblemsRegistry->shouldSkipValidation(
            EntityType::CLASS_TYPE->value,
            $entityId,
            'ClassFinalMethodsCheck',
            $phpVersion
        )) {
            $reason = $this->knownProblemsRegistry->getSkipReason(
                EntityType::CLASS_TYPE->value,
                $entityId,
                'ClassFinalMethodsCheck',
                $phpVersion
            );
            $results->addSuccess($entityId . ' (skipped: ' . $reason . ')');
            return $results;
        }

        $reflection = $this->reflectionProvider->getReflection($phpVersion);

        $reflectionClass = null;
        foreach ($reflection->getClasses() as $class) {
            if ($class->getId() === $entityId) {
                $reflectionClass = $class;
                break;
            }
        }

        if ($reflectionClass === null) {
            $results->addFailure($entityId, "Class {$entityId} not found in reflection data");
            return $results;
        }

        $stubClass = null;
        foreach ($stubs->getClasses() as $class) {
            if ($class->getId() === $entityId) {
                $stubClass = $class;
                break;
            }
        }

        if ($stubClass === null) {
            $results->addFailure($entityId, "Class {$entityId} not found in stubs");
            return $results;
        }

        // Build map: effectiveName → PHPMethod, version-filtered, child definition wins
        $stubMethodMap = $this->collectVersionedStubMethods($stubClass, $phpVersion);

        $hasMismatch = false;
        foreach ($reflectionClass->getMethods() as $reflMethod) {
            $name = $reflMethod->getName();
            if ($name === null) {
                continue;
            }

            if (!isset($stubMethodMap[$name])) {
                // Method absent from stubs — ClassMethodsExistCheck's responsibility
                continue;
            }

            $stubMethod = $stubMethodMap[$name];
            $reflIsFinal = $reflMethod->isFinal();
            $stubIsFinal = $stubMethod->isFinal();

            if ($reflIsFinal === $stubIsFinal) {
                continue;
            }

            $hasMismatch = true;
            $methodEntityId = $entityId . '::' . $name;

            if ($this->knownProblemsRegistry->shouldSkipValidation(
                EntityType::METHOD->value,
                $methodEntityId,
                'ClassFinalMethodsCheck',
                $phpVersion
            )) {
                $reason = $this->knownProblemsRegistry->getSkipReason(
                    EntityType::METHOD->value,
                    $methodEntityId,
                    'ClassFinalMethodsCheck',
                    $phpVersion
                );
                $results->addSuccess($methodEntityId . ' (skipped: ' . $reason . ')');
            } else {
                $expected = $reflIsFinal ? 'final' : 'non-final';
                $actual   = $stubIsFinal ? 'final' : 'non-final';
                $results->addFailure(
                    $methodEntityId,
                    "Method {$methodEntityId} is {$expected} in PHP {$phpVersion} but {$actual} in stubs"
                );
            }
        }

        if (!$hasMismatch) {
            $results->addSuccess($entityId);
        }

        return $results;
    }

    /**
     * Collect version-filtered stub methods from the full class hierarchy.
     * Child class definitions win over parent class definitions for the same name.
     *
     * @return array<string, PHPMethod>
     */
    private function collectVersionedStubMethods(PHPClass $class, string $phpVersion): array
    {
        $methodMap = [];
        $visited   = [];

        $current = $class;
        while ($current !== null) {
            $id = $current->getId();
            if ($id !== null && in_array($id, $visited, true)) {
                break; // cycle guard
            }
            if ($id !== null) {
                $visited[] = $id;
            }

            $this->collectMethodsFromClassLike($current, $phpVersion, $methodMap);

            foreach ($current->getImplementedInterfaces() as $interface) {
                $this->collectMethodsFromInterfaceHierarchy($interface, $phpVersion, $methodMap, $visited);
            }

            $current = $current->parentClass;
        }

        return $methodMap;
    }

    /**
     * Add version-available methods from a single class-like entity to the map.
     * Only inserts a name if not already present (first/child definition wins).
     *
     * @param array<string, PHPMethod> $methodMap
     */
    private function collectMethodsFromClassLike(PHPClassLikeObject $entity, string $phpVersion, array &$methodMap): void
    {
        foreach ($entity->getMethods() as $method) {
            $name = $method->getName();
            if ($name === null) {
                continue;
            }

            $sinceVersion   = $method->getSinceVersion();
            $removedVersion = $method->getRemovedVersion();

            $available = ($sinceVersion === null || version_compare($phpVersion, $sinceVersion, '>='))
                && ($removedVersion === null || version_compare($phpVersion, $removedVersion, '<='));

            if (!$available) {
                continue;
            }

            $effectiveName = str_starts_with($name, 'PS_UNRESERVE_PREFIX_')
                ? substr($name, strlen('PS_UNRESERVE_PREFIX_'))
                : $name;

            if (!isset($methodMap[$effectiveName])) {
                $methodMap[$effectiveName] = $method;
            }
        }
    }

    /**
     * Recursively collect methods from an interface and its parent interface chain.
     *
     * @param array<string, PHPMethod> $methodMap
     * @param array<string>            $visited
     */
    private function collectMethodsFromInterfaceHierarchy(
        PHPInterface $interface,
        string $phpVersion,
        array &$methodMap,
        array &$visited
    ): void {
        $id = $interface->getId();
        if ($id !== null && in_array($id, $visited, true)) {
            return;
        }
        if ($id !== null) {
            $visited[] = $id;
        }

        $this->collectMethodsFromClassLike($interface, $phpVersion, $methodMap);

        foreach ($interface->getParentInterfaces() as $parent) {
            $this->collectMethodsFromInterfaceHierarchy($parent, $phpVersion, $methodMap, $visited);
        }
    }
}
