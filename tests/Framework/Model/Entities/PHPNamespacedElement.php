<?php

namespace StubTests\Sources\Model\Entities;

class PHPNamespacedElement extends BasePHPElement
{
    private ?string $namespace;

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function setNamespace(?string $namespace): void
    {
        $this->namespace = $namespace;
    }
}