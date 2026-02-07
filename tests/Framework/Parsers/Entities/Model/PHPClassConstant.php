<?php

namespace StubTests\Sources\Parsers\Entities\Model;

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