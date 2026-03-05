<?php

namespace StubTests\Sources\Parsers\Entities\Model;

use StubTests\Sources\Parsers\Entities\Model\Types\IntersectionType;
use StubTests\Sources\Parsers\Entities\Model\Types\NoType;
use StubTests\Sources\Parsers\Entities\Model\Types\NullableType;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Parsers\Entities\Model\Types\UnionType;

class PHPParameter
{

    private string $name;
    private StandaloneType|UnionType|NullableType|NoType|IntersectionType $type;
    private int $position;
    private bool $isOptional;
    private bool $isVariadic;
    private bool $isPassedByReference;
    private $defaultValue;
    private bool $hasDefaultValue;
    private ?\Closure $defaultValueEvaluator = null;
    private ?array $languageLevelTypes = null;
    private ?string $defaultType = null;
    private ?string $typeFromPhpDoc = null;
    private ?string $sinceVersion = null;
    private ?string $removedVersion = null;

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

    public function getDeclaredType(): StandaloneType|UnionType|NullableType|NoType|IntersectionType
    {
        return $this->type;
    }

    public function setType(StandaloneType|UnionType|NullableType|NoType|IntersectionType $type): void
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

    public function getDefaultValue(): mixed
    {
        if ($this->defaultValueEvaluator !== null) {
            try {
                $this->defaultValue = ($this->defaultValueEvaluator)();
            } catch (\RuntimeException) {
                $this->defaultValue = null;
            }
            $this->defaultValueEvaluator = null;
        }
        return $this->defaultValue;
    }

    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
        $this->defaultValueEvaluator = null;
    }

    public function setDefaultValueEvaluator(\Closure $evaluator): void
    {
        $this->defaultValueEvaluator = $evaluator;
    }

    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }

    public function setHasDefaultValue(bool $hasDefaultValue): void
    {
        $this->hasDefaultValue = $hasDefaultValue;
    }

    public function getLanguageLevelTypes(): ?array
    {
        return $this->languageLevelTypes;
    }

    public function setLanguageLevelTypes(?array $languageLevelTypes): void
    {
        $this->languageLevelTypes = $languageLevelTypes;
    }

    public function getDefaultType(): ?string
    {
        return $this->defaultType;
    }

    public function setDefaultType(?string $defaultType): void
    {
        $this->defaultType = $defaultType;
    }

    public function getTypeFromPhpDoc(): ?string
    {
        return $this->typeFromPhpDoc;
    }

    public function setTypeFromPhpDoc(?string $typeFromPhpDoc): void
    {
        $this->typeFromPhpDoc = $typeFromPhpDoc;
    }

    public function getSinceVersion(): ?string
    {
        return $this->sinceVersion;
    }

    public function setSinceVersion(?string $sinceVersion): void
    {
        $this->sinceVersion = $sinceVersion;
    }

    public function getRemovedVersion(): ?string
    {
        return $this->removedVersion;
    }

    public function setRemovedVersion(?string $removedVersion): void
    {
        $this->removedVersion = $removedVersion;
    }
}