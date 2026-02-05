<?php

namespace StubTests\Sources\Model\Entities;

class PHPFunction extends PHPNamespacedElement
{

    private $returnTypesFromSignature;
    private bool $isDeprecated;
    private array $parameters;

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
}