<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Model\Entities\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ConstantNode;

/**
 * Parses ConstantNode AST nodes into PHPClassConstant domain objects.
 * Extracts all constant metadata: name, visibility modifiers (PHP 7.1+), final flag (PHP 8.1+).
 */
class StubConstantParser
{
    /**
     * Parses a constant AST node into PHPClassConstant domain object.
     *
     * @param ConstantNode $node The constant AST node
     * @return PHPClassConstant
     */
    public function parseNode(ConstantNode $node): PHPClassConstant
    {
        $constant = new PHPClassConstant();
        $constant->setName($node->getName());

        // Visibility (PHP 7.1+)
        if ($node->isPublic()) {
            $constant->visibility = 'public';
        } elseif ($node->isProtected()) {
            $constant->visibility = 'protected';
        } elseif ($node->isPrivate()) {
            $constant->visibility = 'private';
        }

        // Final flag (PHP 8.1+)
        $constant->isFinal = $node->isFinal();

        return $constant;
    }
}
