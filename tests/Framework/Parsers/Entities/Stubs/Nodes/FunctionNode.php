<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Nodes;

/**
 * Parser-agnostic interface for function AST nodes.
 * Implementations wrap specific parser library nodes (e.g., nikic/php-parser).
 */
interface FunctionNode
{
    /**
     * Get the function name.
     */
    public function getName(): string;

    /**
     * Get the function parameters.
     *
     * @return ParameterNode[]
     */
    public function getParameters(): array;

    /**
     * Get the return type node, or null if no return type.
     */
    public function getReturnType(): ?TypeNode;

    /**
     * Get the doc comment node, or null if no doc comment.
     */
    public function getDocComment(): ?DocCommentNode;

    /**
     * Set the namespace for this function.
     */
    public function setNamespace(string $namespace): void;

    /**
     * Get the namespace for this function.
     */
    public function getNamespace(): string;
}
