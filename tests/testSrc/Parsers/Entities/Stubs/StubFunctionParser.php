<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Model\Entities\PHPFunction;
use StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic\NikicNodeExtractor;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\FunctionNode;

/**
 * Parses PHP function nodes from AST into PHPFunction domain objects.
 * Parser-agnostic: works with any AST node implementing FunctionNode interface.
 * Uses dedicated parser for child entities (parameters).
 */
class StubFunctionParser
{
    public NodeExtractorInterface $nodeExtractor;
    private StubParameterParser $parameterParser;

    public function __construct(?NodeExtractorInterface $nodeExtractor = null)
    {
        $this->nodeExtractor = $nodeExtractor ?? new NikicNodeExtractor();
        $this->parameterParser = new StubParameterParser();
    }

    /**
     * Parses stub code string into PHPFunction.
     * This is a convenience method that delegates to parseNode().
     *
     * @param string $stubCode PHP stub code
     * @return PHPFunction
     */
    public function parse(string $stubCode): PHPFunction
    {
        return $this->parseNode($this->nodeExtractor->extractFunction($stubCode));
    }

    /**
     * Parses a function AST node into PHPFunction domain object.
     * Works with any FunctionNode implementation (parser-agnostic).
     *
     * @param FunctionNode $node The function AST node with namespace set
     * @return PHPFunction
     */
    public function parseNode(FunctionNode $node): PHPFunction
    {
        $phpFunction = new PHPFunction();

        // Basic properties
        $phpFunction->setName($node->getName());
        $phpFunction->setNamespace($node->getNamespace());

        // Set ID
        if ($phpFunction->getNamespace() === '\\') {
            $phpFunction->setId('\\' . $phpFunction->getName());
        } else {
            $phpFunction->setId($phpFunction->getNamespace() . '\\' . $phpFunction->getName());
        }

        // Check deprecation from docblock
        $isDeprecated = false;
        $docComment = $node->getDocComment();
        if ($docComment) {
            $docText = $docComment->getText();
            $isDeprecated = str_contains($docText, '@deprecated');
        }
        $phpFunction->setDeprecated($isDeprecated);

        // Parse parameters
        $parameters = [];
        foreach ($node->getParameters() as $param) {
            $parameters[] = $this->parameterParser->parseNode($param);
        }
        $phpFunction->setParameters($parameters);

        // Parse return type
        $returnType = $node->getReturnType();
        if ($returnType) {
            $phpFunction->setReturnTypeFromSignature($returnType->toString());
        }

        return $phpFunction;
    }
}
