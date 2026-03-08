<?php

namespace StubTests\Sources\Parsers\Entities\Model\Types;

class StandaloneType
{
    private string $typeName;
    public function __construct(string $typeName)
    {
        $this->typeName = $typeName;
    }

    public function toString(): string
    {
        return $this->typeName;
    }
}