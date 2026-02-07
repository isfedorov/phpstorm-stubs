<?php

namespace StubTests\Sources\Parsers\Entities\Model;

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