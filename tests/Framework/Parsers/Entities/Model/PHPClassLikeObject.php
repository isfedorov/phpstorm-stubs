<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class PHPClassLikeObject extends PHPNamespacedElement
{
    private array $methods = [];
    private array $constants = [];

    public function getConstants(): array
    {
        return $this->constants;
    }

    public function setConstants(array $constants): void
    {
        $this->constants = $constants;
    }

    public function addConstant(PHPClassConstant $constant): void
    {
        $this->constants[] = $constant;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function setMethods(array $methods): void
    {
        $this->methods = $methods;
    }

    public function addMethod(PHPMethod $method): void
    {
        $this->methods[] = $method;
    }
}
