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
}
