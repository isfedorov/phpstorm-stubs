<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Since;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\AttributeNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\DocCommentNode;

/**
 * PhpDoc parser implementation using phpDocumentor library.
 * Parses PhpDoc comments and extracts type hints, version information, and other metadata.
 */
class PhpDocumentorParser implements PhpDocParserInterface
{
    private ?DocBlockFactory $factory = null;

    /**
     * Get or create the DocBlockFactory instance (lazy initialization).
     */
    private function getFactory(): DocBlockFactory
    {
        if ($this->factory === null) {
            $this->factory = DocBlockFactory::createInstance();
        }
        return $this->factory;
    }

    /**
     * @inheritDoc
     */
    public function parseDocComment(?string $docComment): ParsedPhpDoc
    {
        if ($docComment === null || trim($docComment) === '') {
            return new ParsedPhpDoc();
        }

        $parsed = new ParsedPhpDoc(rawPhpDoc: $docComment);

        try {
            $docBlock = $this->getFactory()->create($docComment);
        } catch (\Exception $e) {
            // If parsing fails, try to extract @deprecated from raw text
            $parsed->isDeprecated = str_contains($docComment, '@deprecated');
            return $parsed;
        }

        // Extract all information from DocBlock
        $parsed->returnType = $this->extractReturnType($docBlock);
        $parsed->paramTypes = $this->extractParamTypes($docBlock);
        $parsed->varType = $this->extractVarType($docBlock);
        $parsed->sinceVersion = $this->extractSinceVersion($docBlock);
        $parsed->removedVersion = $this->extractRemovedVersion($docBlock);
        $parsed->isDeprecated = $this->hasDeprecatedTag($docBlock);

        return $parsed;
    }

    /**
     * @inheritDoc
     */
    public function parseElementPhpDoc(?DocCommentNode $docComment, array $attributes): ParsedPhpDoc
    {
        // Parse PhpDoc only - version merging is handled by AvailableVersionParser
        $docText = $docComment?->getText();
        return $this->parseDocComment($docText);
    }

    /**
     * Extract the @return type from a DocBlock.
     */
    private function extractReturnType(DocBlock $docBlock): ?string
    {
        $returnTags = $docBlock->getTagsByName('return');
        if (empty($returnTags)) {
            return null;
        }

        $returnTag = $returnTags[0];
        if ($returnTag instanceof Return_) {
            $type = $returnTag->getType();
            return $type !== null ? (string) $type : null;
        }

        return null;
    }

    /**
     * Extract all @param types from a DocBlock as a map of parameter name => type.
     * Parameter names are returned without the $ prefix.
     */
    private function extractParamTypes(DocBlock $docBlock): array
    {
        $paramTypesMap = [];
        $paramTags = $docBlock->getTagsByName('param');

        foreach ($paramTags as $paramTag) {
            if ($paramTag instanceof Param) {
                $varName = $paramTag->getVariableName();
                $type = $paramTag->getType();

                if ($varName !== null && $type !== null) {
                    // Remove $ prefix if present
                    $varName = ltrim($varName, '$');
                    $paramTypesMap[$varName] = (string) $type;
                }
            }
        }

        return $paramTypesMap;
    }

    /**
     * Extract the @var type from a DocBlock (typically used for properties).
     */
    private function extractVarType(DocBlock $docBlock): ?string
    {
        $varTags = $docBlock->getTagsByName('var');
        if (empty($varTags)) {
            return null;
        }

        $varTag = $varTags[0];
        if ($varTag instanceof Var_) {
            $type = $varTag->getType();
            return $type !== null ? (string) $type : null;
        }

        return null;
    }

    /**
     * Extract the @since version from a DocBlock.
     */
    private function extractSinceVersion(DocBlock $docBlock): ?string
    {
        $sinceTags = $docBlock->getTagsByName('since');
        if (empty($sinceTags)) {
            return null;
        }

        $sinceTag = $sinceTags[0];
        if ($sinceTag instanceof Since) {
            return $sinceTag->getVersion();
        }

        return null;
    }

    /**
     * Extract the @removed version from a DocBlock.
     * @removed is typically a Generic tag in phpDocumentor.
     */
    private function extractRemovedVersion(DocBlock $docBlock): ?string
    {
        $removedTags = $docBlock->getTagsByName('removed');
        if (empty($removedTags)) {
            return null;
        }

        $removedTag = $removedTags[0];
        if ($removedTag instanceof Generic) {
            return (string) $removedTag->getDescription();
        }

        return null;
    }

    /**
     * Check if a DocBlock has a @deprecated tag.
     */
    private function hasDeprecatedTag(DocBlock $docBlock): bool
    {
        $deprecatedTags = $docBlock->getTagsByName('deprecated');
        return !empty($deprecatedTags);
    }
}
