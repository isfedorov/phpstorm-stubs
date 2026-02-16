<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Nodes;

/**
 * Parser-agnostic interface for parameter AST nodes.
 */
interface ParameterNode
{
    /**
     * Get the parameter name.
     */
    public function getName(): string;

    /**
     * Get the parameter type hint from signature.
     */
    public function getType(): ?TypeNode;

    /**
     * Get the parameter attributes (PHP 8.0+).
     *
     * @return AttributeNode[]
     */
    public function getAttributes(): array;
}
