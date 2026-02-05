<?php

namespace StubTests\Sources\Model\Entities;

class PHPProperty extends BasePHPElement
{
    private $accessModifier;
    private bool $isStatic = false;
    private bool $isReadonly = false;
    private $type = null;

    public function getAccess()
    {
        return $this->accessModifier;
    }

    public function setAccess($accessModifier)
    {
        $this->accessModifier = $accessModifier;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    public function setIsStatic(bool $isStatic)
    {
        $this->isStatic = $isStatic;
    }

    public function isReadonly(): bool
    {
        return $this->isReadonly;
    }

    public function setIsReadonly(bool $isReadonly)
    {
        $this->isReadonly = $isReadonly;
    }

    public function setTypeFromSignature($typeString)
    {
        $this->type = $typeString;
    }

    public function getType()
    {
        return $this->type;
    }
}