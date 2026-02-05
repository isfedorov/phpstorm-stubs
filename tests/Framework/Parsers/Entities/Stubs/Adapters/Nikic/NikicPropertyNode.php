<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic;

use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\DocCommentNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\PropertyNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\TypeNode;

/**
 * Adapter for nikic/php-parser PropertyProperty nodes.
 * Implements complete PropertyNode interface.
 * Requires both PropertyProperty (for name) and Property statement (for modifiers/type).
 */
class NikicPropertyNode implements PropertyNode
{
    private PropertyProperty $property;
    private Property $propertyStmt;

    public function __construct(PropertyProperty $property, Property $propertyStmt)
    {
        $this->property = $property;
        $this->propertyStmt = $propertyStmt;
    }

    public function getName(): string
    {
        return $this->property->name->toString();
    }

    public function isPublic(): bool
    {
        return $this->propertyStmt->isPublic();
    }

    public function isProtected(): bool
    {
        return $this->propertyStmt->isProtected();
    }

    public function isPrivate(): bool
    {
        return $this->propertyStmt->isPrivate();
    }

    public function isStatic(): bool
    {
        return $this->propertyStmt->isStatic();
    }

    public function isReadonly(): bool
    {
        return $this->propertyStmt->isReadonly();
    }

    public function getType(): ?TypeNode
    {
        if ($this->propertyStmt->type === null) {
            return null;
        }
        return new NikicTypeNode($this->propertyStmt->type);
    }

    public function getDocComment(): ?DocCommentNode
    {
        $docComment = $this->propertyStmt->getDocComment();
        if ($docComment === null) {
            return null;
        }
        return new NikicDocCommentNode($docComment);
    }
}
