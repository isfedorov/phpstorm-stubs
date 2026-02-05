<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic;

use PhpParser\Node;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\TypeNode;

/**
 * Adapter for nikic/php-parser type nodes.
 */
class NikicTypeNode implements TypeNode
{
    private Node $typeNode;

    public function __construct(Node $typeNode)
    {
        $this->typeNode = $typeNode;
    }

    public function toString(): string
    {
        return $this->parseType($this->typeNode);
    }

    private function parseType(Node $typeNode): string
    {
        if ($typeNode instanceof \PhpParser\Node\Identifier) {
            return $typeNode->toString();
        } elseif ($typeNode instanceof \PhpParser\Node\Name) {
            return $typeNode->toString();
        } elseif ($typeNode instanceof \PhpParser\Node\UnionType) {
            $types = [];
            foreach ($typeNode->types as $type) {
                $types[] = $this->parseType($type);
            }
            return implode('|', $types);
        } elseif ($typeNode instanceof \PhpParser\Node\NullableType) {
            return $this->parseType($typeNode->type) . '|null';
        }

        return '';
    }
}
