<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Model\Entities\PHPMethod;
use StubTests\Sources\Model\Entities\PrivateAccessModifier;
use StubTests\Sources\Model\Entities\ProtectedAccessModifier;
use StubTests\Sources\Model\Entities\PublicAccessModifier;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\MethodNode;

/**
 * Parses MethodNode AST nodes into PHPMethod domain objects.
 * Extracts all method metadata: name, access modifiers, static/final/abstract flags, deprecation.
 */
class StubMethodParser
{
    /**
     * Parses a method AST node into PHPMethod domain object.
     *
     * @param MethodNode $node The method AST node
     * @return PHPMethod
     */
    public function parseNode(MethodNode $node): PHPMethod
    {
        $method = new PHPMethod();
        $method->setName($node->getName());

        // Access modifiers
        if ($node->isPublic()) {
            $method->setAccess(new PublicAccessModifier());
        } elseif ($node->isProtected()) {
            $method->setAccess(new ProtectedAccessModifier());
        } elseif ($node->isPrivate()) {
            $method->setAccess(new PrivateAccessModifier());
        }

        // Method modifiers
        $method->setIsStatic($node->isStatic());
        $method->setIsFinal($node->isFinal());
        $method->setIsAbstract($node->isAbstract());

        // Check @deprecated in docblock
        $docComment = $node->getDocComment();
        if ($docComment) {
            $isDeprecated = str_contains($docComment->getText(), '@deprecated');
            $method->setIsDeprecated($isDeprecated);
        } else {
            $method->setIsDeprecated(false);
        }

        // TODO: Parse parameters and return type in future enhancement
        // For now we focus on complete method metadata extraction

        return $method;
    }
}
