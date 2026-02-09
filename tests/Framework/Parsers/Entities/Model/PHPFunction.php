<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class PHPFunction extends PHPNamespacedElement
{

    protected $returnTypesFromSignature;
    protected bool $isDeprecated;
    protected array $parameters;
    protected bool $hasTentativeReturnType;

    public function getReturnTypeFromSignature()
    {
        return $this->returnTypesFromSignature;
    }

    public function setReturnTypeFromSignature($returnTypesFromSignature): void
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
}