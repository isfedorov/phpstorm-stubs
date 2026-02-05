<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Nodes;

/**
 * Parser-agnostic interface for constant AST nodes.
 * Exposes all constant properties needed for complete parsing.
 */
interface ConstantNode
{
    /**
     * Get the constant name.
     */
    public function getName(): string;

    /**
     * Check if the constant is public (PHP 7.1+).
     */
    public function isPublic(): bool;

    /**
     * Check if the constant is protected (PHP 7.1+).
     */
    public function isProtected(): bool;

    /**
     * Check if the constant is private (PHP 7.1+).
     */
    public function isPrivate(): bool;

    /**
     * Check if the constant is final (PHP 8.1+).
     */
    public function isFinal(): bool;

    /**
     * Get the doc comment, or null if no doc comment.
     */
    public function getDocComment(): ?DocCommentNode;
}
