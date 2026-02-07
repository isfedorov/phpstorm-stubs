<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class PHPEnum extends PHPClassLikeObject
{
    public $isFinal;
    public $isReadonly;

    /** @var PHPInterface[] */
    public array $interfaces = [];

    public function getImplementedInterfaces()
    {
        return $this->interfaces;
    }
}
