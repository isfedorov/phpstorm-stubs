<?php

namespace StubTests\Sources\Model\Entities;

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