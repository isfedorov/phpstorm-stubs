<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Parsers\Entities\Stubs\Nodes\TypeNode;

/**
 * Interface for parsing type information from multiple sources.
 * Implementations extract and consolidate type data from:
 * - Native PHP type hints (signature)
 * - PhpDoc annotations
 * - PHP attributes (e.g., LanguageLevelTypeAware)
 */
interface TypeParserInterface
{
    /**
     * Parse type information from all available sources.
     *
     * @param TypeNode|null $signatureType The type from PHP signature/declaration
     * @param string|null $phpDocType The type from PhpDoc (@var, @param, @return)
     * @param array $attributes Array of AttributeNode objects to extract type attributes
     * @return ParsedType Consolidated type information
     */
    public function parseType(
        ?TypeNode $signatureType,
        ?string $phpDocType,
        array $attributes
    ): ParsedType;
}
