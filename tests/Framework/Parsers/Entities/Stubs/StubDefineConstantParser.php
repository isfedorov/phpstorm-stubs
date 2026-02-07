<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use StubTests\Sources\Parsers\Entities\Model\PHPConstant;
use StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic\NikicNodeExtractor;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ConstantDefinitionNode;

/**
 * Parses global define() constant nodes from AST into PHPConstant domain objects.
 * Parser-agnostic: works with any AST node implementing ConstantDefinitionNode interface.
 */
class StubDefineConstantParser
{
    public NodeExtractorInterface $nodeExtractor;

    public function __construct(?NodeExtractorInterface $nodeExtractor = null)
    {
        $this->nodeExtractor = $nodeExtractor ?? new NikicNodeExtractor();
    }

    /**
     * Parses stub code string into PHPConstant array.
     * This is a convenience method that parses all define() calls in the code.
     *
     * @param string $stubCode PHP stub code
     * @return PHPConstant[]
     */
    public function parse(string $stubCode): array
    {
        $nodes = $this->nodeExtractor->extractAllDefineConstants($stubCode);
        $constants = [];
        foreach ($nodes as $node) {
            $constants[] = $this->parseNode($node);
        }
        return $constants;
    }

    /**
     * Parses a constant definition AST node into PHPConstant domain object.
     * Works with any ConstantDefinitionNode implementation (parser-agnostic).
     *
     * @param ConstantDefinitionNode $node The constant definition AST node with namespace set
     * @return PHPConstant
     */
    public function parseNode(ConstantDefinitionNode $node): PHPConstant
    {
        $phpConstant = new PHPConstant();

        // Basic properties
        $phpConstant->setName($node->getName());
        $phpConstant->setNamespace($node->getNamespace());

        // Set ID: if namespace is root (\), don't double the backslash
        if ($phpConstant->getNamespace() === '\\') {
            $phpConstant->setId('\\' . $phpConstant->getName());
        } else {
            $phpConstant->setId($phpConstant->getNamespace() . '\\' . $phpConstant->getName());
        }

        // Extract value - try to get scalar values
        $value = $node->getValue();
        if ($value instanceof String_) {
            $phpConstant->value = $value->value;
        } elseif ($value instanceof LNumber) {
            $phpConstant->value = $value->value;
        } elseif ($value instanceof DNumber) {
            $phpConstant->value = $value->value;
        } else {
            // For complex expressions, store null or a string representation
            $phpConstant->value = null;
        }

        return $phpConstant;
    }
}
