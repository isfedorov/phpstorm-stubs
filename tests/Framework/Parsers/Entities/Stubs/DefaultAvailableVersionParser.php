<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

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
        $availability = AttributeHelper::parsePhpStormStubsElementAvailable($attributes);
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
}
