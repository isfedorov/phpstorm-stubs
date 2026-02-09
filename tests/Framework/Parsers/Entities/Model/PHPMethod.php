<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class PHPMethod extends PHPFunction
{
    private $accessModifier;
    private bool $isStatic;
    private bool $isFinal;
    private bool $isAbstract;

    public function getAccess()
    {
        return $this->accessModifier;
    }

    public function setAccess($accessModifier)
    {
        $this->accessModifier = $accessModifier;
    }

    public function isStatic()
    {
        return $this->isStatic;
    }

    public function setIsStatic(bool $isStatic)
    {
        $this->isStatic = $isStatic;
    }

    public function isFinal()
    {
        return $this->isFinal;
    }

    public function setIsFinal(bool $isFinal)
    {
        $this->isFinal = $isFinal;
    }

    public function isAbstract()
    {
        return $this->isAbstract;
    }

    public function setIsAbstract(bool $isAbstract)
    {
        $this->isAbstract = $isAbstract;
    }
}