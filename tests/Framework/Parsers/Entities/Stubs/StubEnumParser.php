<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic\NikicNodeExtractor;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\EnumNode;

/**
 * Parses PHP enum nodes from AST into PHPEnum domain objects.
 * Parser-agnostic: works with any AST node implementing EnumNode interface.
 * Uses dedicated parsers for child entities (methods).
 */
class StubEnumParser
{
    public NodeExtractorInterface $nodeExtractor;
    private StubMethodParser $methodParser;

    public function __construct(?NodeExtractorInterface $nodeExtractor = null)
    {
        $this->nodeExtractor = $nodeExtractor ?? new NikicNodeExtractor();
        $this->methodParser = new StubMethodParser();
    }

    /**
     * Parses stub code string into PHPEnum.
     * This is a convenience method that delegates to parseNode().
     *
     * @param string $stubCode PHP stub code
     * @return PHPEnum
     */
    public function parse(string $stubCode): PHPEnum
    {
        return $this->parseNode($this->nodeExtractor->extractAllEnums($stubCode)[0] ?? throw new \RuntimeException('No enum found'));
    }

    /**
     * Parses an enum AST node into PHPEnum domain object.
     * Works with any EnumNode implementation (parser-agnostic).
     *
     * @param EnumNode $node The enum AST node with namespace set
     * @return PHPEnum
     */
    public function parseNode(EnumNode $node): PHPEnum
    {
        $phpEnum = new PHPEnum();

        // Basic properties
        $phpEnum->setName($node->getName());
        $phpEnum->setNamespace($node->getNamespace());

        // Set ID: if namespace is root (\), don't double the backslash
        if ($phpEnum->getNamespace() === '\\') {
            $phpEnum->setId('\\' . $phpEnum->getName());
        } else {
            $phpEnum->setId($phpEnum->getNamespace() . '\\' . $phpEnum->getName());
        }

        // Enum-specific properties
        $phpEnum->isFinal = $node->isFinal(); // Always true for enums
        $phpEnum->isReadonly = false; // Enums are not readonly

        // Implemented interfaces
        foreach ($node->getImplementedInterfaceNames() as $interfaceName) {
            $phpInterface = new PHPInterface();
            $phpInterface->setName($interfaceName);
            $phpEnum->interfaces[] = $phpInterface;
        }

        // Methods
        foreach ($node->getMethods() as $methodNode) {
            $phpEnum->methods[] = $this->methodParser->parseNode($methodNode);
        }

        // Note: Enum cases are not currently stored in PHPEnum model
        // The model appears to focus on methods and interfaces
        // Case names can be retrieved via $node->getCaseNames() if needed in future

        return $phpEnum;
    }
}
