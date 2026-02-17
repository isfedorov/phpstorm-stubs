<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Framework\Parsers\Entities\Model\Access\PrivateAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\ProtectedAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\PublicAccessModifier;
use StubTests\Framework\Parsers\Entities\Stubs\PhpDoc\PhpDocParserInterface;
use StubTests\Framework\Parsers\Entities\Stubs\PhpDoc\PhpDocumentorParser;
use StubTests\Framework\Parsers\Entities\Stubs\Types\DefaultTypeParser;
use StubTests\Framework\Parsers\Entities\Stubs\Types\TypeParserInterface;
use StubTests\Framework\Parsers\Entities\Stubs\Versions\AvailableVersionParserInterface;
use StubTests\Framework\Parsers\Entities\Stubs\Versions\DefaultAvailableVersionParser;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\PropertyNode;

/**
 * Parses PropertyNode AST nodes into PHPProperty domain objects.
 * Extracts all property metadata: name, access modifiers, static/readonly flags, type hint.
 */
class StubPropertyParser
{
    private PhpDocParserInterface $phpDocParser;
    private TypeParserInterface $typeParser;
    private AvailableVersionParserInterface $versionParser;

    public function __construct(
        ?PhpDocParserInterface $phpDocParser = null,
        ?TypeParserInterface $typeParser = null,
        ?AvailableVersionParserInterface $versionParser = null
    ) {
        $this->phpDocParser = $phpDocParser ?? new PhpDocumentorParser();
        $this->typeParser = $typeParser ?? new DefaultTypeParser();
        $this->versionParser = $versionParser ?? new DefaultAvailableVersionParser();
    }

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

        // Parse PhpDoc using injected parser
        $parsedPhpDoc = $this->phpDocParser->parseElementPhpDoc($node->getDocComment());

        // Parse type using injected type parser
        $parsedType = $this->typeParser->parseType(
            $node->getType(),
            $parsedPhpDoc->varType,
            $node->getAttributes()
        );

        // Apply parsed PhpDoc data to property
        $property->setPhpDoc($parsedPhpDoc->rawPhpDoc);

        // Parse and apply available version (from PhpDoc + attributes)
        $versions = $this->versionParser->parseAvailableVersion($parsedPhpDoc, $node->getAttributes());
        $property->setSinceVersion($versions['sinceVersion']);
        $property->setRemovedVersion($versions['removedVersion']);

        // Apply parsed type data to property
        // typeFromSignature is always set (NoType if no type)
        $property->setTypeFromSignature($parsedType->typeFromSignature);
        $property->setTypeFromPhpDoc($parsedType->typeFromPhpDoc);
        $property->setLanguageLevelTypes($parsedType->languageLevelTypes);
        $property->setDefaultType($parsedType->defaultType);

        return $property;
    }
}
