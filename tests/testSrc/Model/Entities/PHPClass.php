<?php

namespace StubTests\Sources\Model\Entities;

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

}
