<?php

namespace StubTests\Sources\Model\Entities;

use StubTests\Sources\Model\Entities\PHPNamespacedElement;

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
