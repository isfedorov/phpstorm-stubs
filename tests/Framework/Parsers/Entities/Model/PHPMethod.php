<?php

namespace StubTests\Sources\Parsers\Entities\Model;

use StubTests\Framework\Parsers\Entities\Model\Access\AccessModifier;

class PHPMethod extends PHPFunction
{
    private ?AccessModifier $accessModifier = null;
    private bool $isStatic = false;
    private bool $isFinal = false;
    private bool $isAbstract = false;

    public function getAccess(): ?AccessModifier
    {
        return $this->accessModifier;
    }

    public function setAccess(AccessModifier $accessModifier): void
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