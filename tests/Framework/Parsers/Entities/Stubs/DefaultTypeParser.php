<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Parsers\Entities\Stubs\Nodes\TypeNode;

/**
 * Default implementation of TypeParserInterface.
 * Extracts type information from signature, PhpDoc, and LanguageLevelTypeAware attributes.
 */
class DefaultTypeParser implements TypeParserInterface
{
    private TypeNodeConverter $converter;

    public function __construct(?TypeNodeConverter $converter = null)
    {
        $this->converter = $converter ?? new TypeNodeConverter();
    }

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
    ): ParsedType {
        $parsed = new ParsedType();

        // Convert type from signature to type object
        $parsed->typeFromSignature = $this->converter->convert($signatureType);

        // Store type from PhpDoc
        $parsed->typeFromPhpDoc = $phpDocType;

        // Extract LanguageLevelTypeAware attribute
        $this->parseLanguageLevelTypeAware($attributes, $parsed);

        return $parsed;
    }

    /**
     * Extracts LanguageLevelTypeAware attribute data and stores it in ParsedType.
     *
     * @param array $attributes Array of AttributeNode objects
     * @param ParsedType $parsed The ParsedType to populate
     */
    private function parseLanguageLevelTypeAware(array $attributes, ParsedType $parsed): void
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'LanguageLevelTypeAware' ||
                $attribute->getName() === 'JetBrains\PhpStorm\Internal\LanguageLevelTypeAware') {
                $args = $attribute->getArguments();

                // First argument is the language level type map (array)
                if (isset($args[0]) && is_array($args[0])) {
                    $parsed->languageLevelTypes = $args[0];
                }

                // Named 'default' argument is the default type
                if (isset($args['default'])) {
                    $parsed->defaultType = $args['default'];
                }

                break; // Only process the first LanguageLevelTypeAware attribute
            }
        }
    }
}
