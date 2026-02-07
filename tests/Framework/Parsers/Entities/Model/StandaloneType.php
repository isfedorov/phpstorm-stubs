<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class StandaloneType
{
    private string $typeName;
    public function __construct(string $typeName)
    {
        $this->typeName = $typeName;
    }

    public function toString()
    {
        return $this->typeName;
    }
}