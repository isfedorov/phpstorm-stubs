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
     * @param array $imports Map of import aliases to fully qualified names
     * @param string $namespace Current namespace context (e.g., '\Dom' or '\\' for global)
     * @return PHPMethod
     */
    public function parseNode(MethodNode $node, array $imports = [], string $namespace = '\\'): PHPMethod
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
        $parsedPhpDoc = $this->phpDocParser->parseElementPhpDoc($node->getDocComment());

        // Parse return type using injected type parser with namespace context
        $parsedReturnType = $this->typeParser->parseType(
            $node->getReturnType(),
            $parsedPhpDoc->returnType,
            $node->getAttributes(),
            $imports,
            $namespace
        );

        // Apply parsed PhpDoc data to method
        $method->setPhpDoc($parsedPhpDoc->rawPhpDoc);
        $method->setDeprecated($parsedPhpDoc->isDeprecated || $this->hasDeprecatedAttribute($node->getAttributes(), $imports));
        $method->setHasTentativeReturnType($this->hasTentativeTypeAttribute($node->getAttributes(), $imports));

        // Parse and apply available version (from PhpDoc + attributes)
        $versions = $this->versionParser->parseAvailableVersion($parsedPhpDoc, $node->getAttributes(), $imports);
        $method->setSinceVersion($versions['sinceVersion']);
        $method->setRemovedVersion($versions['removedVersion']);

        // Apply parsed return type data to method
        // typeFromSignature is always set (NoType if no type)
        $method->setReturnTypeFromSignature($parsedReturnType->typeFromSignature);
        $method->setReturnTypeFromPhpDoc($parsedReturnType->typeFromPhpDoc);
        $method->setLanguageLevelTypes($parsedReturnType->languageLevelTypes);
        $method->setDefaultType($parsedReturnType->defaultType);

        // Parse parameters with @param types from PhpDoc, imports, namespace, and optional flags
        $parameters = [];
        foreach ($node->getParameters() as $param) {
            $parameters[] = $this->parameterParser->parseNode($param, $parsedPhpDoc->paramTypes, $imports, $namespace, $parsedPhpDoc->optionalParams);
        }
        $method->setParameters($parameters);

        return $method;
    }

    /**
     * Check whether any attribute in the list is JetBrains\PhpStorm\Internal\TentativeType.
     *
     * @param array $attributes Array of AttributeNode objects
     * @param array $imports    Map of import aliases to fully qualified names
     */
    private function hasTentativeTypeAttribute(array $attributes, array $imports): bool
    {
        foreach ($attributes as $attribute) {
            $name     = $attribute->getName();
            $fullName = $imports[$name] ?? $name;
            if ($fullName === 'JetBrains\\PhpStorm\\Internal\\TentativeType'
                || $fullName === 'TentativeType'
                || str_ends_with($fullName, '\\Internal\\TentativeType')
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check whether any attribute in the list resolves to a deprecation marker.
     * Handles both JetBrains `#[JetBrains\PhpStorm\Deprecated]` and the built-in PHP `#[Deprecated]`.
     *
     * @param array $attributes Array of AttributeNode objects
     * @param array $imports    Map of import aliases to fully qualified names
     */
    private function hasDeprecatedAttribute(array $attributes, array $imports): bool
    {
        foreach ($attributes as $attribute) {
            $name     = $attribute->getName();
            $fullName = $imports[$name] ?? $name;
            if ($fullName === 'JetBrains\\PhpStorm\\Deprecated'
                || str_ends_with($fullName, '\\PhpStorm\\Deprecated')
                || $fullName === 'Deprecated'
            ) {
                return true;
            }
        }
        return false;
    }
}
