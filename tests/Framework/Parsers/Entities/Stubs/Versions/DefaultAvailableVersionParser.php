<?php

namespace StubTests\Framework\Parsers\Entities\Stubs\Versions;

use StubTests\Framework\Parsers\Entities\Stubs\PhpDoc\ParsedPhpDoc;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\AttributeNode;

/**
 * Default implementation of available version parser.
 *
 * Merges version information from PhpDoc tags (@since/@removed) and attributes
 * (PhpStormStubsElementAvailable), with attributes taking precedence.
 */
class DefaultAvailableVersionParser implements AvailableVersionParserInterface
{
    /**
     * @inheritDoc
     */
    public function parseAvailableVersion(ParsedPhpDoc $phpDoc, array $attributes, array $imports = []): array
    {
        // Start with PhpDoc versions
        $sinceVersion = $phpDoc->sinceVersion;
        $removedVersion = $phpDoc->removedVersion;

        // Override with attribute values if present (attributes take precedence)
        $availability = $this->parsePhpStormStubsElementAvailable($attributes, $imports);
        if ($availability['from'] !== null) {
            $sinceVersion = $availability['from'];
        }
        if ($availability['to'] !== null) {
            $removedVersion = $availability['to'];
        }

        return [
            'sinceVersion' => $sinceVersion,
            'removedVersion' => $removedVersion,
        ];
    }

    /**
     * Parse PhpStormStubsElementAvailable attribute to extract from/to versions.
     * Returns ['from' => version|null, 'to' => version|null].
     *
     * @param array $attributes Array of AttributeNode objects
     * @param array $imports Map of import aliases to fully qualified names
     * @return array ['from' => string|null, 'to' => string|null]
     */
    private function parsePhpStormStubsElementAvailable(array $attributes, array $imports): array
    {
        $result = ['from' => null, 'to' => null];

        foreach ($attributes as $attribute) {
            if (!($attribute instanceof AttributeNode)) {
                continue;
            }

            $name = $attribute->getName();

            // Resolve the attribute name through imports if it's an alias
            $fullName = $this->resolveAttributeName($name, $imports);

            if ($this->isPhpStormStubsElementAvailable($fullName)) {
                $args = $attribute->getArguments();

                // Extract 'from' parameter
                if (isset($args['from'])) {
                    $result['from'] = (string)$args['from'];
                } elseif (isset($args[0])) {
                    $result['from'] = (string)$args[0];
                }

                // Extract 'to' parameter
                if (isset($args['to'])) {
                    $result['to'] = (string)$args['to'];
                } elseif (isset($args[1])) {
                    $result['to'] = (string)$args[1];
                }

                break; // Only process the first matching attribute
            }
        }

        return $result;
    }

    /**
     * Resolve an attribute name through imports.
     * If the name is an alias, returns the fully qualified name.
     * Otherwise, returns the name as-is.
     *
     * @param string $name Attribute name (may be alias or FQN)
     * @param array $imports Map of import aliases to fully qualified names
     * @return string Fully qualified attribute name or original name
     */
    private function resolveAttributeName(string $name, array $imports): string
    {
        // If it's an alias in the imports map, resolve it
        if (isset($imports[$name])) {
            return $imports[$name];
        }

        // Already fully qualified or not aliased
        return $name;
    }

    /**
     * Check if a fully qualified name represents PhpStormStubsElementAvailable.
     *
     * @param string $fullName Fully qualified attribute name
     * @return bool True if this is the PhpStormStubsElementAvailable attribute
     */
    private function isPhpStormStubsElementAvailable(string $fullName): bool
    {
        // Check for exact matches with various forms of the attribute name
        return $fullName === 'JetBrains\\PhpStorm\\Internal\\PhpStormStubsElementAvailable' ||
               $fullName === 'PhpStormStubsElementAvailable' ||
               // Handle cases where the name might end with the class name
               str_ends_with($fullName, '\\PhpStormStubsElementAvailable');
    }
}
