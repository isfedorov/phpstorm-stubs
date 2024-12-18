<?php

namespace StubTests\Parsers\Helpers;

use PhpParser\Node;

class IdentifierHelper
{

    /**
     * @param Node $node
     * @return string
     */
    public static function buildFullyQualifiedName(Node $node)
    {
        $fqn = '';
        foreach ($node->parts as $part) {
            $fqn .= "$part\\";
        }
        return $fqn;
    }

    /**
     * @return string
     */
    public static function getShortName(Node $node)
    {
        $fqn = self::getFQN($node);
        $parts = explode('\\', $fqn);
        return array_pop($parts);
    }

    /**
     * @return string
     */
    public static function getFQN(Node $node)
    {
        if (property_exists($node, 'namespacedName') && $node->namespacedName !== null) {
            return "\\$node->namespacedName";
        }
        if (property_exists($node, 'name')) {
            return $node->name;
        }
        return IdentifierHelper::buildFullyQualifiedName($node);
    }
}