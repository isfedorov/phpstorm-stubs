<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

/**
 * Interface for parsing available version information from PhpDoc and attributes.
 *
 * Implementations should merge version data from both PhpDoc tags (@since/@removed)
 * and attributes (PhpStormStubsElementAvailable), applying consistent precedence rules.
 */
interface AvailableVersionParserInterface
{
    /**
     * Parse available version information from PhpDoc and attributes.
     *
     * Precedence: Attributes (PhpStormStubsElementAvailable) override PhpDoc tags.
     *
     * @param ParsedPhpDoc $phpDoc Already-parsed PhpDoc data containing @since/@removed
     * @param array $attributes Array of AttributeNode objects to check for PhpStormStubsElementAvailable
     * @return array Array with keys 'sinceVersion' and 'removedVersion', values are ?string
     */
    public function parseAvailableVersion(ParsedPhpDoc $phpDoc, array $attributes): array;
}
