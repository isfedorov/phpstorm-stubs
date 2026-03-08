<?php

namespace StubTests\Sources\Parsers\Entities\Model\Types;

class IntersectionType
{
    /** @var StandaloneType[] */
    private array $types = [];

    public function addType(StandaloneType $type): void
    {
        $this->types[] = $type;
    }

    public function toString(): string
    {
        return implode('&', array_map(fn(StandaloneType $type) => $type->toString(), $this->types));
    }

    public function containsTypes(string ...$types): bool
    {
        foreach ($types as $type) {
            if (!in_array($type, array_map(fn(StandaloneType $type) => $type->toString(), $this->types))) {
                return false;
            }
        }
        return true;
    }
}
