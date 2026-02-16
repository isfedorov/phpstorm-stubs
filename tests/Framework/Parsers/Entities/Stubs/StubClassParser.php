<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic\NikicNodeExtractor;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ClassNode;

/**
 * Parses PHP class nodes from AST into PHPClass domain objects.
 * Parser-agnostic: works with any AST node implementing ClassNode interface.
 * Uses dedicated parsers for child entities (methods, properties, constants).
 */
class StubClassParser
{
    public NodeExtractorInterface $nodeExtractor;
    private PhpDocParserInterface $phpDocParser;
    private TypeParserInterface $typeParser;
    private AvailableVersionParserInterface $versionParser;
    private StubMethodParser $methodParser;
    private StubPropertyParser $propertyParser;
    private StubConstantParser $constantParser;

    public function __construct(
        ?NodeExtractorInterface $nodeExtractor = null,
        ?PhpDocParserInterface $phpDocParser = null,
        ?TypeParserInterface $typeParser = null,
        ?AvailableVersionParserInterface $versionParser = null
    ) {
        $this->nodeExtractor = $nodeExtractor ?? new NikicNodeExtractor();
        $this->phpDocParser = $phpDocParser ?? new PhpDocumentorParser();
        $this->typeParser = $typeParser ?? new DefaultTypeParser();
        $this->versionParser = $versionParser ?? new DefaultAvailableVersionParser();
        $this->methodParser = new StubMethodParser($phpDocParser, $typeParser, $versionParser);
        $this->propertyParser = new StubPropertyParser($phpDocParser, $typeParser, $versionParser);
        $this->constantParser = new StubConstantParser();
    }

    /**
     * Parses stub code string into PHPClass.
     * This is a convenience method that delegates to parseNode().
     *
     * @param string $stubCode PHP stub code
     * @return PHPClass
     */
    public function parse(string $stubCode): PHPClass
    {
        return $this->parseNode($this->nodeExtractor->extractClass($stubCode));
    }

    /**
     * Parses a class AST node into PHPClass domain object.
     * Works with any ClassNode implementation (parser-agnostic).
     *
     * @param ClassNode $node The class AST node with namespace set
     * @return PHPClass
     */
    public function parseNode(ClassNode $node): PHPClass
    {
        $phpClass = new PHPClass();

        // Basic properties
        $phpClass->setName($node->getName());
        $phpClass->setNamespace($node->getNamespace());

        // Set ID: if namespace is root (\), don't double the backslash
        if ($phpClass->getNamespace() === '\\') {
            $phpClass->setId('\\' . $phpClass->getName());
        } else {
            $phpClass->setId($phpClass->getNamespace() . '\\' . $phpClass->getName());
        }

        $phpClass->isFinal = $node->isFinal();
        $phpClass->isReadonly = $node->isReadonly();

        // Parse PhpDoc using injected parser
        $parsedPhpDoc = $this->phpDocParser->parseElementPhpDoc(
            $node->getDocComment(),
            $node->getAttributes()
        );

        // Apply parsed PhpDoc data to class
        $phpClass->setPhpDoc($parsedPhpDoc->rawPhpDoc);

        // Parse and apply available version (from PhpDoc + attributes)
        $versions = $this->versionParser->parseAvailableVersion($parsedPhpDoc, $node->getAttributes());
        $phpClass->setSinceVersion($versions['sinceVersion']);
        $phpClass->setRemovedVersion($versions['removedVersion']);

        // Parent class
        $parentClassName = $node->getParentClassName();
        if ($parentClassName) {
            $parentClass = new PHPClass();
            $parentClass->setName($parentClassName);
            $phpClass->parentClass = $parentClass;
        }

        // Interfaces
        foreach ($node->getInterfaceNames() as $interfaceName) {
            $phpInterface = new PHPInterface();
            $phpInterface->setName($interfaceName);
            $phpClass->interfaces[] = $phpInterface;
        }

        // Methods
        foreach ($node->getMethods() as $methodNode) {
            $phpClass->methods[] = $this->methodParser->parseNode($methodNode);
        }

        // Properties
        foreach ($node->getProperties() as $propertyNode) {
            $phpClass->properties[] = $this->propertyParser->parseNode($propertyNode);
        }

        // Constants
        foreach ($node->getConstants() as $constantNode) {
            $phpClass->constants[] = $this->constantParser->parseNode($constantNode);
        }

        return $phpClass;
    }
}