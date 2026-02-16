<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Parsers\Entities\Stubs\Nodes\AttributeNode;

/**
 * Helper for parsing PHP attributes.
 */
class AttributeHelper
{
    /**
     * Parse PhpStormStubsElementAvailable attribute to extract from/to versions.
     * Returns ['from' => version|null, 'to' => version|null].
     *
     * @param array $attributes Array of AttributeNode objects
     * @return array ['from' => string|null, 'to' => string|null]
     */
    public static function parsePhpStormStubsElementAvailable(array $attributes): array
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
                    $result['from'] = (string) $args['from'];
                } elseif (isset($args[0])) {
                    $result['from'] = (string) $args[0];
                }

                // Extract 'to' parameter
                if (isset($args['to'])) {
                    $result['to'] = (string) $args['to'];
                } elseif (isset($args[1])) {
                    $result['to'] = (string) $args[1];
                }

                break; // Only process the first matching attribute
            }
        }

        return $result;
    }
}
