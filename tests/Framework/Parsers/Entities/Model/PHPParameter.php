<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class PHPParameter
{

    private string $name;

    public function __construct(?string $name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDeclaredType()
    {
        return new StandaloneType('int');
    }
}