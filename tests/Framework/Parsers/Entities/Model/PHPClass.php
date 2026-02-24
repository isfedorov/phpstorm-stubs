<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class PHPClass extends PHPClassLikeObject
{
    public $isFinal;
    public $isReadonly;

    public array $properties = [];
    /** @var PHPClass */
    public $parentClass = null;
    /** @var PHPInterface[] */
    public array $interfaces = [];

    public function getImplementedInterfaces()
    {
        return $this->interfaces;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getParentClass()
    {
        return $this->parentClass;
    }

    /**
     * Returns the FQN (without leading backslash) of all ancestor classes by traversing
     * the parentClass chain.
     *
     * When ClassHierarchyResolver has linked parentClass objects to their actual instances,
     * getId() carries the fully-qualified name (e.g. "\Random\RandomError"). The leading
     * backslash is stripped so the result matches the format PHP reflection returns
     * (e.g. "Random\RandomError"). For unresolved stubs (getId() is null) the stored
     * getName() value is used as a fallback.
     *
     * @return string[] Ancestor FQNs (no leading \) from direct parent to most distant ancestor
     */
    public function getAncestorClassNames(): array
    {
        $ancestors = [];
        $current = $this->parentClass;
        $visited = [];

        while ($current !== null) {
            $id = $current->getId();
            // Linked objects carry their full namespace in getId(); strip the leading \
            // to match reflection's format. Fall back to getName() for unresolved stubs.
            $name = $id !== null ? ltrim($id, '\\') : $current->getName();
            if ($name === null || $name === '' || isset($visited[$name])) {
                break;
            }
            $ancestors[] = $name;
            $visited[$name] = true;
            $current = $current->parentClass;
        }

        return $ancestors;
    }

    /**
     * Returns the names of all directly implemented interfaces.
     *
     * @return string[]
     */
    public function getImplementedInterfaceNames(): array
    {
        $names = [];
        foreach ($this->interfaces as $interface) {
            $name = $interface->getName();
            if ($name !== null && $name !== '') {
                $names[] = $name;
            }
        }
        return $names;
    }

}
