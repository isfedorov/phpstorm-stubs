<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Framework\Parsers\Entities\Stubs\PhpDoc\PhpDocParserInterface;
use StubTests\Framework\Parsers\Entities\Stubs\PhpDoc\PhpDocumentorParser;
use StubTests\Framework\Parsers\Entities\Stubs\Types\DefaultTypeParser;
use StubTests\Framework\Parsers\Entities\Stubs\Types\TypeParserInterface;
use StubTests\Framework\Parsers\Entities\Stubs\Versions\AvailableVersionParserInterface;
use StubTests\Framework\Parsers\Entities\Stubs\Versions\DefaultAvailableVersionParser;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic\NikicNodeExtractor;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\InterfaceNode;

/**
 * Parses PHP interface nodes from AST into PHPInterface domain objects.
 * Parser-agnostic: works with any AST node implementing InterfaceNode interface.
 * Uses dedicated parsers for child entities (methods, constants).
 */
class StubInterfaceParser implements MultiEntityStubParserInterface
{
    public NodeExtractorInterface $nodeExtractor;
    private PhpDocParserInterface $phpDocParser;
    private TypeParserInterface $typeParser;
    private AvailableVersionParserInterface $versionParser;
    private StubMethodParser $methodParser;
    private StubClassConstantParser $constantParser;

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
        $this->constantParser = new StubClassConstantParser();
    }

    /**
     * Parses stub code string into PHPInterface.
     * This is a convenience method that delegates to parseNode().
     *
     * @param string $stubCode PHP stub code
     * @return PHPInterface
     */
    public function parse(string $stubCode): PHPInterface
    {
        return $this->parseNode($this->nodeExtractor->extractAllInterfaces($stubCode)[0] ?? throw new \RuntimeException('No interface found'));
    }

    /**
     * Parses an interface AST node into PHPInterface domain object.
     * Works with any InterfaceNode implementation (parser-agnostic).
     *
     * @param InterfaceNode $node The interface AST node with namespace set
     * @return PHPInterface
     */
    public function parseNode(InterfaceNode $node): PHPInterface
    {
        $phpInterface = new PHPInterface();

        // Basic properties
        $phpInterface->setName($node->getName());
        $phpInterface->setNamespace($node->getNamespace());

        // Set ID: if namespace is root (\), don't double the backslash
        if ($phpInterface->getNamespace() === '\\') {
            $phpInterface->setId('\\' . $phpInterface->getName());
        } else {
            $phpInterface->setId($phpInterface->getNamespace() . '\\' . $phpInterface->getName());
        }

        // Parse PhpDoc using injected parser
        $parsedPhpDoc = $this->phpDocParser->parseElementPhpDoc($node->getDocComment());

        // Apply parsed PhpDoc data to interface
        $phpInterface->setPhpDoc($parsedPhpDoc->rawPhpDoc);

        // Parse and apply available version (from PhpDoc + attributes)
        $versions = $this->versionParser->parseAvailableVersion($parsedPhpDoc, $node->getAttributes());
        $phpInterface->setSinceVersion($versions['sinceVersion']);
        $phpInterface->setRemovedVersion($versions['removedVersion']);

        // Parent interfaces (extends)
        foreach ($node->getParentInterfaceNames() as $parentInterfaceName) {
            $parentInterface = new PHPInterface();
            $parentInterface->setName($parentInterfaceName);
            $parentInterface->setNamespace($node->getNamespace());
            $parentInterface->setId($node->getNamespace() === '\\'
                ? '\\' . $parentInterfaceName
                : $node->getNamespace() . '\\' . $parentInterfaceName);
            $phpInterface->addParentInterface($parentInterface);
        }

        // Methods
        foreach ($node->getMethods() as $methodNode) {
            $phpInterface->methods[] = $this->methodParser->parseNode($methodNode);
        }

        // Constants
        foreach ($node->getConstants() as $constantNode) {
            $phpInterface->constants[] = $this->constantParser->parseNode($constantNode);
        }

        return $phpInterface;
    }

    /**
     * Extract and parse all interfaces from stub content.
     *
     * @param string $stubContent The PHP stub file content to parse
     * @return array Array of PHPInterface objects
     */
    public function extractAndParseAll(string $stubContent): array
    {
        $interfaceNodes = $this->nodeExtractor->extractAllInterfaces($stubContent);
        $interfaces = [];

        foreach ($interfaceNodes as $interfaceNode) {
            $interfaces[] = $this->parseNode($interfaceNode);
        }

        return $interfaces;
    }
}
