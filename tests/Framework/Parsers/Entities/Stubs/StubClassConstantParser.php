<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Framework\Parsers\Entities\Stubs\PhpDoc\PhpDocParserInterface;
use StubTests\Framework\Parsers\Entities\Stubs\PhpDoc\PhpDocumentorParser;
use StubTests\Framework\Parsers\Entities\Stubs\Versions\AvailableVersionParserInterface;
use StubTests\Framework\Parsers\Entities\Stubs\Versions\DefaultAvailableVersionParser;
use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ConstantNode;

/**
 * Parses ConstantNode AST nodes into PHPClassConstant domain objects.
 * Extracts all constant metadata: name, visibility modifiers (PHP 7.1+), final flag (PHP 8.1+),
 * and version availability from @since/@removed PhpDoc tags and PhpStormStubsElementAvailable attributes.
 */
class StubClassConstantParser
{
    private PhpDocParserInterface $phpDocParser;
    private AvailableVersionParserInterface $versionParser;

    public function __construct(
        ?PhpDocParserInterface $phpDocParser = null,
        ?AvailableVersionParserInterface $versionParser = null
    ) {
        $this->phpDocParser = $phpDocParser ?? new PhpDocumentorParser();
        $this->versionParser = $versionParser ?? new DefaultAvailableVersionParser();
    }

    /**
     * Parses a constant AST node into PHPClassConstant domain object.
     *
     * @param ConstantNode $node    The constant AST node
     * @param array        $imports Map of import aliases to fully qualified names
     * @return PHPClassConstant
     */
    public function parseNode(ConstantNode $node, array $imports = []): PHPClassConstant
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
        $constant->setValue($node->getValue());
        // Final flag (PHP 8.1+)
        $constant->isFinal = $node->isFinal();

        // Parse version availability from PhpDoc + attributes
        $parsedPhpDoc = $this->phpDocParser->parseElementPhpDoc($node->getDocComment());
        $versions = $this->versionParser->parseAvailableVersion($parsedPhpDoc, $node->getAttributes(), $imports);
        $constant->setSinceVersion($versions['sinceVersion']);
        $constant->setRemovedVersion($versions['removedVersion']);

        return $constant;
    }
}
