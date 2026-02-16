<?php

namespace StubTests\Sources\Parsers\Entities\Model;

use StubTests\Sources\Parsers\Entities\Model\Types\IntersectionType;
use StubTests\Sources\Parsers\Entities\Model\Types\NoType;
use StubTests\Sources\Parsers\Entities\Model\Types\NullableType;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Parsers\Entities\Model\Types\UnionType;

class PHPFunction extends PHPNamespacedElement
{

    protected StandaloneType|UnionType|NullableType|NoType|IntersectionType|null $returnTypesFromSignature = null;
    protected bool $isDeprecated = false;
    protected array $parameters = [];
    protected bool $hasTentativeReturnType = false;
    protected ?array $languageLevelTypes = null;
    protected ?string $defaultType = null;
    protected ?string $returnTypeFromPhpDoc = null;

    public function getReturnTypeFromSignature(): StandaloneType|UnionType|NullableType|NoType|IntersectionType|null
    {
        return $this->returnTypesFromSignature;
    }

    public function setReturnTypeFromSignature(StandaloneType|UnionType|NullableType|NoType|IntersectionType $returnTypesFromSignature): void
    {
        $this->returnTypesFromSignature = $returnTypesFromSignature;
    }

    public function isDeprecated()
    {
        return $this->isDeprecated;
    }

    public function setDeprecated(bool $isDeprecated)
    {
        $this->isDeprecated = $isDeprecated;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public function setHasTentativeReturnType($hasTentativeReturnType)
    {
        $this->hasTentativeReturnType = $hasTentativeReturnType;
    }

    public function hasTentativeReturnType()
    {
        return $this->hasTentativeReturnType ?? false;
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

    public function getReturnTypeFromPhpDoc(): ?string
    {
        return $this->returnTypeFromPhpDoc;
    }

    public function setReturnTypeFromPhpDoc(?string $returnTypeFromPhpDoc): void
    {
        $this->returnTypeFromPhpDoc = $returnTypeFromPhpDoc;
    }
}