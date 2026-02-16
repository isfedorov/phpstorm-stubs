<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class PHPInterface extends PHPClassLikeObject
{
    private array $parentInterfaces = [];

    public function getParentInterfaces()
    {
        return $this->parentInterfaces;
    }

    public function setParentInterfaces(array $parentInterfaces)
    {
        $this->parentInterfaces = $parentInterfaces;
    }

    public function addParentInterface(PHPInterface $interface)
    {
        $this->parentInterfaces[] = $interface;
    }
}