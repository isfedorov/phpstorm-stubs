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

    public function getImplementedInterfaces(): array
    {
        return $this->interfaces;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getParentClass(): ?PHPClass
    {
        return $this->parentClass;
    }
}
