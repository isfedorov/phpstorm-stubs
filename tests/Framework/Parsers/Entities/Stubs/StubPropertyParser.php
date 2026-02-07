<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\Entities\Model\PrivateAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\ProtectedAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\PublicAccessModifier;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\PropertyNode;

/**
 * Parses PropertyNode AST nodes into PHPProperty domain objects.
 * Extracts all property metadata: name, access modifiers, static/readonly flags, type hint.
 */
class StubPropertyParser
{
    /**
     * Parses a property AST node into PHPProperty domain object.
     *
     * @param PropertyNode $node The property AST node
     * @return PHPProperty
     */
    public function parseNode(PropertyNode $node): PHPProperty
    {
        $property = new PHPProperty();
        $property->setName($node->getName());

        // Access modifiers
        if ($node->isPublic()) {
            $property->setAccess(new PublicAccessModifier());
        } elseif ($node->isProtected()) {
            $property->setAccess(new ProtectedAccessModifier());
        } elseif ($node->isPrivate()) {
            $property->setAccess(new PrivateAccessModifier());
        }

        // Property modifiers
        $property->setIsStatic($node->isStatic());
        $property->setIsReadonly($node->isReadonly());

        // Parse type hint
        $type = $node->getType();
        if ($type) {
            $property->setTypeFromSignature($type->toString());
        }

        return $property;
    }
}
