<?php

namespace StubTests\Sources\Parsers\Entities\Model;

use StubTests\Framework\Parsers\Entities\Model\Access\AccessModifier;
use StubTests\Sources\Parsers\Entities\Model\Types\IntersectionType;
use StubTests\Sources\Parsers\Entities\Model\Types\NoType;
use StubTests\Sources\Parsers\Entities\Model\Types\NullableType;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Parsers\Entities\Model\Types\UnionType;

class PHPProperty extends BasePHPElement
{
    private ?AccessModifier $accessModifier = null;
    private bool $isStatic = false;
    private bool $isReadonly = false;
    private StandaloneType|UnionType|NullableType|NoType|IntersectionType|null $type = null;
    private $defaultValue = null;
    private ?array $languageLevelTypes = null;
    private ?string $defaultType = null;
    private ?string $typeFromPhpDoc = null;

    public function getAccess(): ?AccessModifier
    {
        return $this->accessModifier;
    }

    public function setAccess(AccessModifier $accessModifier): void
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

    public function setTypeFromSignature(StandaloneType|UnionType|NullableType|NoType|IntersectionType $type): void
    {
        $this->type = $type;
    }

    public function getType(): StandaloneType|UnionType|NullableType|NoType|IntersectionType|null
    {
        return $this->type;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    public function hasDefaultValue(): bool
    {
        return $this->defaultValue !== null;
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
}