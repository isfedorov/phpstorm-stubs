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
    public function parseAvailableVersion(ParsedPhpDoc $phpDoc, array $attributes): array
    {
        // Start with PhpDoc versions
        $sinceVersion = $phpDoc->sinceVersion;
        $removedVersion = $phpDoc->removedVersion;

        // Override with attribute values if present (attributes take precedence)
        $availability = $this->parsePhpStormStubsElementAvailable($attributes);
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
     * @return array ['from' => string|null, 'to' => string|null]
     */
    private function parsePhpStormStubsElementAvailable(array $attributes): array
    {
        $result = ['from' => null, 'to' => null];

        foreach ($attributes as $attribute) {
            if (!($attribute instanceof AttributeNode)) {
                continue;
            }

            $name = $attribute->getName();
            if ($name === 'PhpStormStubsElementAvailable' ||
                $name === 'JetBrains\PhpStorm\Internal\PhpStormStubsElementAvailable') {
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
}
