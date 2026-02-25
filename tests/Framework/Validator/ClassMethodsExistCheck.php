<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClassLikeObject;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
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
class ClassMethodsExistCheck implements CheckInterface
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

        // Class-level known problem skips all method validation for this class
        if ($this->knownProblemsRegistry->shouldSkipValidation(
            EntityType::CLASS_TYPE->value,
            $entityId,
            'ClassMethodsExistCheck',
            $phpVersion
        )) {
            $reason = $this->knownProblemsRegistry->getSkipReason(
                EntityType::CLASS_TYPE->value,
                $entityId,
                'ClassMethodsExistCheck',
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

        // Collect all method names from the stub class and its full parent hierarchy,
        // filtered to only those available in the given PHP version.
        // ClassHierarchyResolver has already linked parentClass references, so traversal
        // correctly follows the chain (e.g. Dom\HTMLDocument → Dom\Document → Dom\Node).
        $stubMethodNames = $this->collectVersionedStubMethodNames($stubClass, $phpVersion);

        $missingMethods = array_diff(array_keys($reflectionMethodNames), $stubMethodNames);

        if (empty($missingMethods)) {
            $results->addSuccess($entityId);
            return $results;
        }

        // For each missing method, check for a method-level known problem entry.
        sort($missingMethods);
        foreach ($missingMethods as $methodName) {
            $methodEntityId = $entityId . '::' . $methodName;

            if ($this->knownProblemsRegistry->shouldSkipValidation(
                EntityType::METHOD->value,
                $methodEntityId,
                'ClassMethodsExistCheck',
                $phpVersion
            )) {
                $reason = $this->knownProblemsRegistry->getSkipReason(
                    EntityType::METHOD->value,
                    $methodEntityId,
                    'ClassMethodsExistCheck',
                    $phpVersion
                );
                $results->addSuccess($methodEntityId . ' (skipped: ' . $reason . ')');
            } else {
                $results->addFailure(
                    $methodEntityId,
                    "Method {$methodEntityId} exists in PHP {$phpVersion} but not in stubs"
                );
            }
        }

        return $results;
    }

    /**
     * Collect all unique method names from a stub class and every ancestor in the stub hierarchy
     * that are available for the given PHP version.
     *
     * Traversal includes:
     * - The class itself and its full parentClass chain
     * - All implemented interfaces (and their parent interface chains) for each class in the hierarchy
     *
     * This mirrors how PHP's reflection reports methods for abstract classes that inherit
     * abstract method declarations from interfaces (e.g. ReflectionFunctionAbstract::export
     * from the Reflector interface in PHP 7.x).
     *
     * A method is considered available if:
     * - sinceVersion is null OR phpVersion >= sinceVersion
     * - AND removedVersion is null OR phpVersion <= removedVersion
     *
     * @return array<string>
     */
    private function collectVersionedStubMethodNames(PHPClass $class, string $phpVersion): array
    {
        $names = [];
        $visited = [];

        // Traverse the parent class chain
        $current = $class;
        while ($current !== null) {
            $id = $current->getId();
            if ($id !== null && in_array($id, $visited, true)) {
                break; // cycle guard
            }
            if ($id !== null) {
                $visited[] = $id;
            }

            $this->collectMethodNamesFromClassLike($current, $phpVersion, $names);

            // Also traverse the interfaces of this class (and their parent interface chains)
            foreach ($current->getImplementedInterfaces() as $interface) {
                $this->collectMethodNamesFromInterfaceHierarchy($interface, $phpVersion, $names, $visited);
            }

            $current = $current->parentClass;
        }

        return array_unique($names);
    }

    /**
     * Collect versioned method names from a single class-like entity (class or interface).
     *
     * @param array<string> $names Output array to append to
     */
    private function collectMethodNamesFromClassLike(PHPClassLikeObject $entity, string $phpVersion, array &$names): void
    {
        foreach ($entity->getMethods() as $method) {
            $name = $method->getName();
            if ($name === null) {
                continue;
            }

            $sinceVersion = $method->getSinceVersion();
            $removedVersion = $method->getRemovedVersion();

            $availableSince = $sinceVersion === null
                || version_compare($phpVersion, $sinceVersion, '>=');
            $notRemoved = $removedVersion === null
                || version_compare($phpVersion, $removedVersion, '<=');

            if ($availableSince && $notRemoved) {
                // Strip PS_UNRESERVE_PREFIX_ from method names. In PHP, reserved keywords are
                // valid class method names (e.g. Generator::throw(), IntlCalendar::isSet()).
                // Stub files use the PS_UNRESERVE_PREFIX_ prefix so PhpStorm's parser can
                // handle the file, but reflection always reports the real method name without
                // the prefix. Strip it here so PS_UNRESERVE_PREFIX_throw matches "throw",
                // and PS_UNRESERVE_PREFIX_isSet matches "isSet".
                $effectiveName = str_starts_with($name, 'PS_UNRESERVE_PREFIX_')
                    ? substr($name, strlen('PS_UNRESERVE_PREFIX_'))
                    : $name;
                $names[] = $effectiveName;
            }
        }
    }

    /**
     * Recursively collect versioned method names from an interface and all its parent interfaces.
     *
     * @param array<string> $names Output array to append to
     * @param array<string> $visited IDs already visited (prevents cycles)
     */
    private function collectMethodNamesFromInterfaceHierarchy(PHPInterface $interface, string $phpVersion, array &$names, array &$visited): void
    {
        $id = $interface->getId();
        if ($id !== null && in_array($id, $visited, true)) {
            return;
        }
        if ($id !== null) {
            $visited[] = $id;
        }

        $this->collectMethodNamesFromClassLike($interface, $phpVersion, $names);

        foreach ($interface->getParentInterfaces() as $parent) {
            $this->collectMethodNamesFromInterfaceHierarchy($parent, $phpVersion, $names, $visited);
        }
    }
}
