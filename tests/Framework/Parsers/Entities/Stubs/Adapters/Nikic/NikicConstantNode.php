<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic;

use PhpParser\Node\Const_;
use PhpParser\Node\Stmt\ClassConst;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ConstantNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\DocCommentNode;

/**
 * Adapter for nikic/php-parser Const_ nodes.
 * Implements complete ConstantNode interface.
 * Requires both Const_ (for name) and ClassConst statement (for visibility/final).
 */
class NikicConstantNode implements ConstantNode
{
    private Const_ $constant;
    private ClassConst $classConst;

    public function __construct(Const_ $constant, ClassConst $classConst)
    {
        $this->constant = $constant;
        $this->classConst = $classConst;
    }

    public function getName(): string
    {
        return $this->constant->name->toString();
    }

    public function getValue(): mixed
    {
        return self::resolveExprValue($this->constant->value);
    }

    /**
     * Resolves a PhpParser expression node to a plain PHP value.
     * Returns null for complex expressions (arrays, operations, etc.) that cannot be statically evaluated.
     */
    private static function resolveExprValue(\PhpParser\Node\Expr $expr): mixed
    {
        if ($expr instanceof \PhpParser\Node\Scalar\String_) {
            return $expr->value;
        }
        if ($expr instanceof \PhpParser\Node\Scalar\LNumber) {
            return $expr->value;
        }
        if ($expr instanceof \PhpParser\Node\Scalar\DNumber) {
            return $expr->value;
        }
        if ($expr instanceof \PhpParser\Node\Expr\UnaryMinus) {
            if ($expr->expr instanceof \PhpParser\Node\Scalar\LNumber
                || $expr->expr instanceof \PhpParser\Node\Scalar\DNumber) {
                return -$expr->expr->value;
            }
            return null;
        }
        if ($expr instanceof \PhpParser\Node\Expr\UnaryPlus) {
            if ($expr->expr instanceof \PhpParser\Node\Scalar\LNumber
                || $expr->expr instanceof \PhpParser\Node\Scalar\DNumber) {
                return $expr->expr->value;
            }
            return null;
        }
        return null;
    }

    public function isPublic(): bool
    {
        return $this->classConst->isPublic();
    }

    public function isProtected(): bool
    {
        return $this->classConst->isProtected();
    }

    public function isPrivate(): bool
    {
        return $this->classConst->isPrivate();
    }

    public function isFinal(): bool
    {
        return $this->classConst->isFinal();
    }

    public function getDocComment(): ?DocCommentNode
    {
        $docComment = $this->classConst->getDocComment();
        if ($docComment === null) {
            return null;
        }
        return new NikicDocCommentNode($docComment);
    }

    public function getAttributes(): array
    {
        $attributes = [];
        foreach ($this->classConst->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attributes[] = new NikicAttributeNode($attr);
            }
        }
        return $attributes;
    }
}
