<?php

namespace StubTests\Sources\Model\Entities;

class UnionType
{
    private array $types;
    public function addType(StandaloneType $type)
    {
        $this->types []= $type;
    }

    public function toString()
    {
        return implode('|', array_map(fn(StandaloneType $type) => $type->toString(), $this->types));
    }

    public function containsTypes(string ...$types)
    {
        foreach ($types as $type) {
            if (!in_array($type, array_map(fn(StandaloneType $type) => $type->toString(), $this->types))) {
                return false;
            }
        }
        return true;
    }
}