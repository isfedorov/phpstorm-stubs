<?php

namespace StubTests\Sources\Parsers;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;

/**
 * Resolves parent class and interface references after all entities are loaded.
 *
 * During deserialization, parent class and interface objects are created as stubs
 * containing only the short class name. This resolver replaces those stubs with
 * the actual objects from the loaded collection, enabling full hierarchy traversal
 * via PHPClass::getAncestorClassNames() and PHPClass::getImplementedInterfaceNames().
 */
class ClassHierarchyResolver
{
    /**
     * Link parentClass and interfaces on every PHPClass to the actual objects in the collection.
     * Also links interfaces on every PHPEnum.
     *
     * @param iterable $classes     All PHPClass instances from the storage
     * @param iterable $interfaces  All PHPInterface instances from the storage
     * @param iterable $enums       All PHPEnum instances from the storage
     */
    public function resolve(iterable $classes, iterable $interfaces = [], iterable $enums = []): void
    {
        // Build FQN → object lookup tables (keyed by getId() stripped of the leading \).
        // Using FQN keys eliminates collisions when multiple classes share the same short
        // name in different namespaces (e.g. 16 classes named "Exception" in the stubs).
        $classMap = [];
        foreach ($classes as $class) {
            $id = $class->getId();
            if ($id !== null && $id !== '') {
                $classMap[ltrim($id, '\\')] = $class;
            }
        }

        $interfaceMap = [];
        foreach ($interfaces as $iface) {
            $id = $iface->getId();
            if ($id !== null && $id !== '') {
                $interfaceMap[ltrim($id, '\\')] = $iface;
            }
        }

        // Link each class's parent and interfaces to actual objects
        foreach ($classMap as $class) {
            $this->resolveClass($class, $classMap, $interfaceMap);
        }

        // Link each interface's parent interfaces to actual objects
        foreach ($interfaceMap as $iface) {
            $this->resolveInterface($iface, $interfaceMap);
        }

        // Link each enum's interfaces to actual objects
        foreach ($enums as $enum) {
            $this->resolveEnum($enum, $interfaceMap);
        }
    }

    private function resolveClass(PHPClass $class, array $classMap, array $interfaceMap): void
    {
        // Replace parentClass stub with the actual PHPClass from the collection.
        if ($class->parentClass !== null) {
            $class->parentClass = $this->lookupClass($class->parentClass->getName(), $class, $classMap)
                ?? $class->parentClass;
        }

        // Replace interface stubs with actual PHPInterface objects from the collection.
        foreach ($class->interfaces as $idx => $iface) {
            $resolved = $this->lookupInterface($iface->getName(), $class, $interfaceMap);
            if ($resolved !== null) {
                $class->interfaces[$idx] = $resolved;
            }
        }
    }

    /**
     * Look up a class by the stored name, which may be a FQN (from reflection data or
     * explicitly qualified stubs) or a short/relative name (from cached stubs data).
     *
     * Lookup strategy:
     * 1. Direct match against the FQN-keyed map (handles reflection FQNs and stubs root-
     *    namespace classes whose short name equals their FQN, e.g. "Exception").
     * 2. Namespace-qualified fallback: prepend the owning class's namespace to the short name
     *    and try again (handles stubs where the parent is in the same namespace, e.g.
     *    "RandomError" stored for \Random\BrokenRandomEngineError resolves to "Random\RandomError").
     */
    private function lookupClass(?string $name, PHPClass $owningClass, array $classMap): ?PHPClass
    {
        if ($name === null || $name === '') {
            return null;
        }

        if (isset($classMap[$name])) {
            return $classMap[$name];
        }

        $ns = ltrim($owningClass->getNamespace() ?? '', '\\');
        if ($ns !== '') {
            $qualified = $ns . '\\' . $name;
            if (isset($classMap[$qualified])) {
                return $classMap[$qualified];
            }
        }

        return null;
    }

    private function lookupInterface(?string $name, PHPClass $owningClass, array $interfaceMap): ?PHPInterface
    {
        if ($name === null || $name === '') {
            return null;
        }

        if (isset($interfaceMap[$name])) {
            return $interfaceMap[$name];
        }

        $ns = ltrim($owningClass->getNamespace() ?? '', '\\');
        if ($ns !== '') {
            $qualified = $ns . '\\' . $name;
            if (isset($interfaceMap[$qualified])) {
                return $interfaceMap[$qualified];
            }
        }

        return null;
    }

    /**
     * Resolve parent interface references for a PHPInterface to actual PHPInterface objects.
     */
    private function resolveInterface(PHPInterface $iface, array $interfaceMap): void
    {
        $parentInterfaces = $iface->getParentInterfaces();
        foreach ($parentInterfaces as $idx => $parent) {
            $resolved = $this->lookupInterfaceByName($parent->getName(), $iface, $interfaceMap);
            if ($resolved !== null) {
                $parentInterfaces[$idx] = $resolved;
            }
        }
        $iface->setParentInterfaces($parentInterfaces);
    }

    private function resolveEnum(PHPEnum $enum, array $interfaceMap): void
    {
        foreach ($enum->interfaces as $idx => $iface) {
            $resolved = $this->lookupInterfaceForEnum($iface->getName(), $enum, $interfaceMap);
            if ($resolved !== null) {
                $enum->interfaces[$idx] = $resolved;
            }
        }
    }

    private function lookupInterfaceForEnum(?string $name, PHPEnum $owningEnum, array $interfaceMap): ?PHPInterface
    {
        if ($name === null || $name === '') {
            return null;
        }

        if (isset($interfaceMap[$name])) {
            return $interfaceMap[$name];
        }

        $ns = ltrim($owningEnum->getNamespace() ?? '', '\\');
        if ($ns !== '') {
            $qualified = $ns . '\\' . $name;
            if (isset($interfaceMap[$qualified])) {
                return $interfaceMap[$qualified];
            }
        }

        return null;
    }

    private function lookupInterfaceByName(?string $name, PHPInterface $owningInterface, array $interfaceMap): ?PHPInterface
    {
        if ($name === null || $name === '') {
            return null;
        }

        if (isset($interfaceMap[$name])) {
            return $interfaceMap[$name];
        }

        $ns = ltrim($owningInterface->getNamespace() ?? '', '\\');
        if ($ns !== '') {
            $qualified = $ns . '\\' . $name;
            if (isset($interfaceMap[$qualified])) {
                return $interfaceMap[$qualified];
            }
        }

        return null;
    }
}
