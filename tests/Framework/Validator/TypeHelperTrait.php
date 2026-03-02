<?php

namespace StubTests\Sources\Validator;

/**
 * Provides shared type-resolution and normalisation helpers for validators
 * that compare type information between reflection data and stubs.
 *
 * Methods:
 * - resolveVersionAwareType() – resolves a type from LanguageLevelTypeAware attribute data
 * - normalizeType()           – normalises a type string for semantic comparison
 */
trait TypeHelperTrait
{
    /**
     * Resolve version-aware type from LanguageLevelTypeAware attribute data.
     *
     * Finds the highest version key in languageLevelTypes that is <= $phpVersion.
     * Falls back to defaultType when no version entry applies.
     * Returns null when neither languageLevelTypes nor defaultType is set.
     *
     * @param mixed  $entity     Any object with getLanguageLevelTypes() and getDefaultType()
     * @param string $phpVersion PHP version (e.g., '8.0')
     * @return string|null Resolved type or null if no LanguageLevelTypeAware data
     */
    private function resolveVersionAwareType(mixed $entity, string $phpVersion): ?string
    {
        if (!method_exists($entity, 'getLanguageLevelTypes') || !method_exists($entity, 'getDefaultType')) {
            return null;
        }

        $languageLevelTypes = $entity->getLanguageLevelTypes();
        $defaultType        = $entity->getDefaultType();

        if ($languageLevelTypes === null && $defaultType === null) {
            return null;
        }

        $applicableType           = null;
        $highestApplicableVersion = null;

        if (is_array($languageLevelTypes)) {
            foreach ($languageLevelTypes as $version => $type) {
                if (version_compare($phpVersion, (string) $version, '>=')) {
                    if ($highestApplicableVersion === null || version_compare((string) $version, $highestApplicableVersion, '>')) {
                        $highestApplicableVersion = (string) $version;
                        $applicableType           = $type;
                    }
                }
            }
        }

        return $applicableType ?? $defaultType;
    }

    /**
     * Normalize a type string for semantic comparison.
     *
     * Handles:
     * - Typed array notation (string[], int[], etc.) → array
     * - Union type ordering (sort components alphabetically)
     * - Leading backslashes on class names (for FQN consistency)
     *
     * @param string|null $type Type string to normalize, or null if no type information
     * @return string|null Normalized type string, or null if input was null
     */
    private function normalizeType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        // Handle typed arrays: convert string[], int[], etc. to array
        $type = preg_replace('/\b(\w+)\[\]/', 'array', $type);

        // Handle union types: sort components for consistent comparison
        if (str_contains($type, '|')) {
            $parts = explode('|', $type);
            $parts = array_map(fn($part) => ltrim(trim($part), '\\'), $parts);
            $parts = array_unique($parts);
            sort($parts);
            $type = implode('|', $parts);
        } else {
            $type = ltrim($type, '\\');
        }

        return $type;
    }
}
