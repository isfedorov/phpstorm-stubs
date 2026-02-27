<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClassLikeObject;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\ParsedDataStorageManager;

abstract class AbstractClassCheck extends AbstractReflectionCheck
{
    /**
     * Find a class by entity ID in the given storage (works for both reflection and stubs).
     */
    protected function findClassById(ParsedDataStorageManager $storage, string $entityId): ?PHPClass
    {
        foreach ($storage->getClasses() as $class) {
            if ($class->getId() === $entityId) {
                return $class;
            }
        }
        return null;
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
    protected function collectVersionedStubPropertiesMap(PHPClass $class, string $phpVersion): array
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

    /**
     * Collect version-filtered stub methods from the full class hierarchy.
     * Child class definitions win over parent class definitions for the same effective name.
     *
     * Traversal includes:
     * - The class itself and its full parentClass chain
     * - All implemented interfaces (and their parent interface chains) for each class in the hierarchy
     *
     * A method is considered available if:
     * - sinceVersion is null OR phpVersion >= sinceVersion
     * - AND removedVersion is null OR phpVersion <= removedVersion
     *
     * @return array<string, PHPMethod>
     */
    protected function collectVersionedStubMethods(PHPClass $class, string $phpVersion): array
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
     * Strips PS_UNRESERVE_PREFIX_ from method names so that e.g. PS_UNRESERVE_PREFIX_throw
     * matches the reflection name "throw".
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
