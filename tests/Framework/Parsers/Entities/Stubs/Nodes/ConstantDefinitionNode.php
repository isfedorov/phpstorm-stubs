<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Nodes;

/**
 * Parser-agnostic interface for global constant definition AST nodes.
 * Represents define() function calls that define global constants.
 */
interface ConstantDefinitionNode
{
    /**
     * Get the constant name.
     */
    public function getName(): string;

    /**
     * Get the constant value.
     * This can be a scalar value or a complex expression node.
     */
    public function getValue();

    /**
     * Get the namespace where this constant is defined.
     */
    public function getNamespace(): string;

    /**
     * Set the namespace for this constant.
     */
    public function setNamespace(string $namespace): void;
}
