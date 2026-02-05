<?php

namespace StubTests\Sources\Model\Entities;

class PHPMethod extends BasePHPElement
{
    private $accessModifier;
    private bool $isStatic;
    private bool $isFinal;
    private bool $isAbstract;
    private bool $isDeprecated;

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

    public function isDeprecated()
    {
        return $this->isDeprecated;
    }

    public function setIsDeprecated(bool $isDeprecated)
    {
        $this->isDeprecated = $isDeprecated;
    }
}