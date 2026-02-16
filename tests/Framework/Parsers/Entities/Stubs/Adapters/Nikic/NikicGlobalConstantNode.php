<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic;

use PhpParser\Node\Const_;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ConstantDefinitionNode;

/**
 * Adapter for nikic/php-parser Const_ nodes representing global const declarations.
 * Wraps individual constants from const statements (e.g., const A = 1, B = 2).
 * Provides parser-agnostic access to constant properties.
 */
class NikicGlobalConstantNode implements ConstantDefinitionNode
{
    private Const_ $const;
    private string $namespace = '\\';

    public function __construct(Const_ $const)
    {
        $this->const = $const;
    }

    public function getName(): string
    {
        return $this->const->name->toString();
    }

    public function getValue()
    {
        // Return the value expression
        // This could be a scalar (String_, LNumber, DNumber) or a more complex expression
        return $this->const->value;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }
}
