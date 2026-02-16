<?php

namespace StubTests\Sources\Parsers\Entities\Model\Types;

class NullableType
{
    private StandaloneType $basicType;

    public function toString()
    {
        return $this->basicType->toString() != 'mixed' ? "{$this->basicType->toString()}|null" : $this->basicType->toString();
    }

    public function toArray()
    {
        return [$this->basicType->toString(), 'null'];
    }

    public function addBasicType(StandaloneType $param)
    {
        $this->basicType = $param;
    }

    public function hasBasicType(string $type): bool
    {
        return $this->basicType->toString() == $type;
    }
}