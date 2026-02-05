<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic;

use PhpParser\Comment\Doc;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\DocCommentNode;

/**
 * Adapter for nikic/php-parser Doc comment nodes.
 */
class NikicDocCommentNode implements DocCommentNode
{
    private Doc $docComment;

    public function __construct(Doc $docComment)
    {
        $this->docComment = $docComment;
    }

    public function getText(): string
    {
        return $this->docComment->getText();
    }
}
