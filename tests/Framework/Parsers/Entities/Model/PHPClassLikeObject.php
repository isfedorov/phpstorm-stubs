<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class PHPClassLikeObject extends PHPNamespacedElement
{
    public array $methods = [];
    public array $constants = [];

    public function getConstants()
    {
        return $this->constants;
    }

    public function getMethods()
    {
        return $this->methods;
    }
}
