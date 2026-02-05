<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Model\Entities\PHPInterface;
use StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic\NikicNodeExtractor;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\InterfaceNode;

/**
 * Parses PHP interface nodes from AST into PHPInterface domain objects.
 * Parser-agnostic: works with any AST node implementing InterfaceNode interface.
 * Uses dedicated parsers for child entities (methods, constants).
 */
class StubInterfaceParser
{
    public NodeExtractorInterface $nodeExtractor;
    private StubMethodParser $methodParser;
    private StubConstantParser $constantParser;

    public function __construct(?NodeExtractorInterface $nodeExtractor = null)
    {
        $this->nodeExtractor = $nodeExtractor ?? new NikicNodeExtractor();
        $this->methodParser = new StubMethodParser();
        $this->constantParser = new StubConstantParser();
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

        // Parent interfaces (extends)
        // Note: PHPInterface doesn't have a parent property structure, interfaces can extend multiple
        // The parent interfaces are stored as names in getParentInterfaceNames()
        // For now we don't persist this relationship in the model

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
}
