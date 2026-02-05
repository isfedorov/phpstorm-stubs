<?php

namespace StubTests\Sources\Model\Entities;

class PHPClassConstant extends BasePHPElement
{
    public $value;
    public $parentId;
    public $visibility = 'public'; // Default to public
    public bool $isFinal = false;

    public function getValue()
    {
        return $this->value;
    }

    public function isFinal(): bool
    {
        return $this->isFinal;
    }
}