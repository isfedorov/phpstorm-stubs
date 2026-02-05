<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\DocCommentNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\EnumNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\MethodNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\TypeNode;

/**
 * Adapter for nikic/php-parser Enum_ nodes.
 * Wraps Enum_ and provides parser-agnostic access to enum properties.
 */
class NikicEnumNode implements EnumNode
{
    private Enum_ $enum;
    private string $namespace = '\\';

    public function __construct(Enum_ $enum)
    {
        $this->enum = $enum;
    }

    public function getName(): string
    {
        return $this->enum->name->toString();
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    public function getBackingType(): ?TypeNode
    {
        if ($this->enum->scalarType === null) {
            return null;
        }
        return new NikicTypeNode($this->enum->scalarType);
    }

    public function getCaseNames(): array
    {
        $caseNames = [];
        foreach ($this->enum->stmts as $stmt) {
            if ($stmt instanceof EnumCase) {
                $caseNames[] = $stmt->name->toString();
            }
        }
        return $caseNames;
    }

    public function getMethods(): array
    {
        $methods = [];
        foreach ($this->enum->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                $methods[] = new NikicMethodNode($stmt);
            }
        }
        return $methods;
    }

    public function getImplementedInterfaceNames(): array
    {
        $names = [];
        foreach ($this->enum->implements as $interface) {
            $names[] = $interface->toString();
        }
        return $names;
    }

    public function isFinal(): bool
    {
        // Enums are implicitly final
        return true;
    }

    public function getDocComment(): ?DocCommentNode
    {
        $docComment = $this->enum->getDocComment();
        if ($docComment === null) {
            return null;
        }
        return new NikicDocCommentNode($docComment);
    }
}
