<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class PHPParameter
{

    private string $name;
    private $type;
    private int $position;
    private bool $isOptional;
    private bool $isVariadic;
    private bool $isPassedByReference;
    private $defaultValue;
    private bool $hasDefaultValue;

    public function __construct(?string $name)
    {
        $this->name = $name ?? '';
        $this->type = new NoType();
        $this->position = 0;
        $this->isOptional = false;
        $this->isVariadic = false;
        $this->isPassedByReference = false;
        $this->defaultValue = null;
        $this->hasDefaultValue = false;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDeclaredType()
    {
        return $this->type;
    }

    public function setType($type): void
    {
        $this->type = $type;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    public function setIsOptional(bool $isOptional): void
    {
        $this->isOptional = $isOptional;
    }

    public function isVariadic(): bool
    {
        return $this->isVariadic;
    }

    public function setIsVariadic(bool $isVariadic): void
    {
        $this->isVariadic = $isVariadic;
    }

    public function isPassedByReference(): bool
    {
        return $this->isPassedByReference;
    }

    public function setIsPassedByReference(bool $isPassedByReference): void
    {
        $this->isPassedByReference = $isPassedByReference;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }

    public function setHasDefaultValue(bool $hasDefaultValue): void
    {
        $this->hasDefaultValue = $hasDefaultValue;
    }
}