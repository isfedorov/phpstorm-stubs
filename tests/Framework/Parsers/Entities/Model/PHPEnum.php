<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class PHPEnum extends PHPClassLikeObject
{
    public $isFinal;
    public $isReadonly;

    /** @var PHPInterface[] */
    public array $interfaces = [];

    /** @var string[] */
    public array $cases = [];

    public function getImplementedInterfaces()
    {
        return $this->interfaces;
    }

    /** @return string[] */
    public function getCaseNames(): array
    {
        return $this->cases;
    }
}
