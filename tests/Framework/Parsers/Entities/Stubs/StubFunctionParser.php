<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
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
    private PhpDocParserInterface $phpDocParser;
    private TypeParserInterface $typeParser;
    private AvailableVersionParserInterface $versionParser;
    private StubParameterParser $parameterParser;

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
        $this->parameterParser = new StubParameterParser($typeParser, $versionParser);
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

        // Parse PhpDoc using injected parser
        $parsedPhpDoc = $this->phpDocParser->parseElementPhpDoc(
            $node->getDocComment(),
            $node->getAttributes()
        );

        // Parse return type using injected type parser
        $parsedReturnType = $this->typeParser->parseType(
            $node->getReturnType(),
            $parsedPhpDoc->returnType,
            $node->getAttributes()
        );

        // Apply parsed PhpDoc data to function
        $phpFunction->setPhpDoc($parsedPhpDoc->rawPhpDoc);
        $phpFunction->setDeprecated($parsedPhpDoc->isDeprecated);

        // Parse and apply available version (from PhpDoc + attributes)
        $versions = $this->versionParser->parseAvailableVersion($parsedPhpDoc, $node->getAttributes());
        $phpFunction->setSinceVersion($versions['sinceVersion']);
        $phpFunction->setRemovedVersion($versions['removedVersion']);

        // Apply parsed return type data to function
        // typeFromSignature is always set (NoType if no type)
        $phpFunction->setReturnTypeFromSignature($parsedReturnType->typeFromSignature);
        $phpFunction->setReturnTypeFromPhpDoc($parsedReturnType->typeFromPhpDoc);
        $phpFunction->setLanguageLevelTypes($parsedReturnType->languageLevelTypes);
        $phpFunction->setDefaultType($parsedReturnType->defaultType);

        // Parse parameters with @param types from PhpDoc
        $parameters = [];
        foreach ($node->getParameters() as $param) {
            $parameters[] = $this->parameterParser->parseNode($param, $parsedPhpDoc->paramTypes);
        }
        $phpFunction->setParameters($parameters);

        return $phpFunction;
    }
}
