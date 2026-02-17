<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Framework\Parsers\Entities\Stubs\PhpDoc\ParsedPhpDoc;
use StubTests\Framework\Parsers\Entities\Stubs\Types\DefaultTypeParser;
use StubTests\Framework\Parsers\Entities\Stubs\Types\TypeParserInterface;
use StubTests\Framework\Parsers\Entities\Stubs\Versions\AvailableVersionParserInterface;
use StubTests\Framework\Parsers\Entities\Stubs\Versions\DefaultAvailableVersionParser;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ParameterNode;

/**
 * Parses ParameterNode AST nodes into PHPParameter domain objects.
 * Extracts parameter name and LanguageLevelTypeAware attributes.
 */
class StubParameterParser
{
    private TypeParserInterface $typeParser;
    private AvailableVersionParserInterface $versionParser;

    public function __construct(
        ?TypeParserInterface $typeParser = null,
        ?AvailableVersionParserInterface $versionParser = null
    ) {
        $this->typeParser = $typeParser ?? new DefaultTypeParser();
        $this->versionParser = $versionParser ?? new DefaultAvailableVersionParser();
    }

    /**
     * Parses a parameter AST node into PHPParameter domain object.
     *
     * @param ParameterNode $node The parameter AST node
     * @param array $paramTypesFromPhpDoc Map of parameter name => type from @param tags
     * @return PHPParameter
     */
    public function parseNode(ParameterNode $node, array $paramTypesFromPhpDoc = []): PHPParameter
    {
        $parameter = new PHPParameter($node->getName());

        // Match parameter type from PhpDoc @param tags
        $paramName = $node->getName();
        $phpDocType = $paramTypesFromPhpDoc[$paramName] ?? null;

        // Parse type using injected type parser
        $parsedType = $this->typeParser->parseType(
            $node->getType(),  // Get parameter type from signature
            $phpDocType,
            $node->getAttributes()
        );

        // Apply parsed type data to parameter
        // typeFromSignature is always set (NoType if no type)
        $parameter->setType($parsedType->typeFromSignature);
        $parameter->setTypeFromPhpDoc($parsedType->typeFromPhpDoc);
        $parameter->setLanguageLevelTypes($parsedType->languageLevelTypes);
        $parameter->setDefaultType($parsedType->defaultType);

        // Parse available version from attributes using version parser
        // Note: Parameters typically don't have PhpDoc, only attributes
        $emptyPhpDoc = new ParsedPhpDoc();
        $versions = $this->versionParser->parseAvailableVersion($emptyPhpDoc, $node->getAttributes());
        $parameter->setSinceVersion($versions['sinceVersion']);
        $parameter->setRemovedVersion($versions['removedVersion']);

        return $parameter;
    }
}
