<?php

namespace StubTests\Sources\Validator\KnownProblems;

use StubTests\Sources\Runner\PhpVersionRange;

/**
 * Immutable value object representing a known validation problem.
 *
 * Defines a specific entity (function/method/class) that has known issues
 * with validation for documented reasons.
 */
readonly class ProblemDefinition
{
    /**
     * @param EntityType $entityType Type of entity (function, method, class)
     * @param string $entityId Fully qualified entity identifier (e.g., "\dba_fetch", "DateTime::format")
     * @param ProblemType $type Category of problem
     * @param CheckType[] $affectedChecks List of validator checks that should skip this entity
     * @param PhpVersionRange $versionRange PHP version range where this problem exists
     * @param string $reason Human-readable explanation of why validation is skipped
     */
    public function __construct(
        public EntityType $entityType,
        public string $entityId,
        public ProblemType $type,
        public array $affectedChecks,
        public PhpVersionRange $versionRange,
        public string $reason
    ) {
        // Validate that affectedChecks only contains CheckType instances
        foreach ($affectedChecks as $check) {
            if (!$check instanceof CheckType) {
                throw new \InvalidArgumentException(
                    'All affected checks must be instances of CheckType enum'
                );
            }
        }
    }

    /**
     * Check if this problem affects a specific check for a given PHP version.
     *
     * @param CheckType $check The validator check to test
     * @param string $phpVersion PHP version (e.g., '8.0')
     * @return bool True if this problem affects the given check and version
     */
    public function affects(CheckType $check, string $phpVersion): bool
    {
        return in_array($check, $this->affectedChecks, true)
            && $this->versionRange->includes($phpVersion);
    }

    /**
     * Check if this problem applies to a given PHP version.
     *
     * @param string $phpVersion PHP version (e.g., '8.0')
     * @return bool True if problem exists in this PHP version
     */
    public function appliesToVersion(string $phpVersion): bool
    {
        return $this->versionRange->includes($phpVersion);
    }
}
