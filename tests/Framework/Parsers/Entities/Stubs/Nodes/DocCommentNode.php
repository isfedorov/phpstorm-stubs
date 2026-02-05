<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Nodes;

/**
 * Parser-agnostic interface for documentation comment nodes.
 */
interface DocCommentNode
{
    /**
     * Get the text content of the doc comment.
     */
    public function getText(): string;
}
