<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Framework\Parsers\Entities\Model\Access\PrivateAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\ProtectedAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\PublicAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\MethodNode;

/**
 * Parses MethodNode AST nodes into PHPMethod domain objects.
 * Extracts all method metadata: name, access modifiers, static/final/abstract flags, deprecation.
 */
class StubMethodParser
{
    private PhpDocParserInterface $phpDocParser;
    private TypeParserInterface $typeParser;
    private AvailableVersionParserInterface $versionParser;
    private StubParameterParser $parameterParser;

    public function __construct(
        ?PhpDocParserInterface $phpDocParser = null,
        ?TypeParserInterface $typeParser = null,
        ?AvailableVersionParserInterface $versionParser = null
    ) {
        $this->phpDocParser = $phpDocParser ?? new PhpDocumentorParser();
        $this->typeParser = $typeParser ?? new DefaultTypeParser();
        $this->versionParser = $versionParser ?? new DefaultAvailableVersionParser();
        $this->parameterParser = new StubParameterParser($typeParser, $versionParser);
    }

    /**
     * Parses a method AST node into PHPMethod domain object.
     *
     * @param MethodNode $node The method AST node
     * @return PHPMethod
     */
    public function parseNode(MethodNode $node): PHPMethod
    {
        $method = new PHPMethod();
        $method->setName($node->getName());

        // Access modifiers
        if ($node->isPublic()) {
            $method->setAccess(new PublicAccessModifier());
        } elseif ($node->isProtected()) {
            $method->setAccess(new ProtectedAccessModifier());
        } elseif ($node->isPrivate()) {
            $method->setAccess(new PrivateAccessModifier());
        }

        // Method modifiers
        $method->setIsStatic($node->isStatic());
        $method->setIsFinal($node->isFinal());
        $method->setIsAbstract($node->isAbstract());

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

        // Apply parsed PhpDoc data to method
        $method->setPhpDoc($parsedPhpDoc->rawPhpDoc);
        $method->setDeprecated($parsedPhpDoc->isDeprecated);

        // Parse and apply available version (from PhpDoc + attributes)
        $versions = $this->versionParser->parseAvailableVersion($parsedPhpDoc, $node->getAttributes());
        $method->setSinceVersion($versions['sinceVersion']);
        $method->setRemovedVersion($versions['removedVersion']);

        // Apply parsed return type data to method
        // typeFromSignature is always set (NoType if no type)
        $method->setReturnTypeFromSignature($parsedReturnType->typeFromSignature);
        $method->setReturnTypeFromPhpDoc($parsedReturnType->typeFromPhpDoc);
        $method->setLanguageLevelTypes($parsedReturnType->languageLevelTypes);
        $method->setDefaultType($parsedReturnType->defaultType);

        // Parse parameters with @param types from PhpDoc
        $parameters = [];
        foreach ($node->getParameters() as $param) {
            $parameters[] = $this->parameterParser->parseNode($param, $parsedPhpDoc->paramTypes);
        }
        $method->setParameters($parameters);

        return $method;
    }
}
