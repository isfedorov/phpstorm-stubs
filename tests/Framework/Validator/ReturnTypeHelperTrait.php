<?php

namespace StubTests\Sources\Validator;

/**
 * Provides shared return-type helpers for FunctionReturnTypesCheck and
 * ClassMethodsReturnTypesCheck.
 *
 * Three methods are extracted here to avoid duplication between the two checks:
 * - getReturnTypeString()    – resolves the effective return type for any callable
 * - resolveVersionAwareType() – handles LanguageLevelTypeAware attribute data
 * - normalizeType()           – normalises a type string for semantic comparison
 */
trait ReturnTypeHelperTrait
{
    /**
     * Get the return type string representation from a function/method.
     * Supports version-aware types via LanguageLevelTypeAware attribute.
     *
     * Priority order:
     * 1. Signature type (if present) — takes precedence over everything
     * 2. LanguageLevelTypeAware (if no signature type)
     * 3. Legacy getReturnType() method (backward compatibility)
     *
     * @param mixed  $callable
     * @param string $phpVersion PHP version (e.g., '8.0')
     * @return string|null Returns null when no return type information is available
     */
    private function getReturnTypeString(mixed $callable, string $phpVersion): ?string
    {
        // Try to get return type from signature first (highest priority)
        $signatureType = null;
        if (method_exists($callable, 'getReturnTypeFromSignature')) {
            $returnType = $callable->getReturnTypeFromSignature();

            if ($returnType !== null) {
                if (is_object($returnType)) {
                    if (method_exists($returnType, '__toString')) {
                        $signatureType = (string) $returnType;
                    } elseif (method_exists($returnType, 'toString')) {
                        $typeString = $returnType->toString();
                        $signatureType = $typeString === '' ? null : $typeString;
                    } elseif (method_exists($returnType, 'getTypeName')) {
                        $signatureType = $returnType->getTypeName();
                    }
                } else {
                    $signatureType = (string) $returnType;
                }
            }
        }

        if ($signatureType !== null && $signatureType !== '') {
            return $signatureType;
        }

        // Check for LanguageLevelTypeAware (second priority)
        $versionAwareType = $this->resolveVersionAwareType($callable, $phpVersion);
        if ($versionAwareType !== null) {
            return $versionAwareType;
        }

        // Try alternative methods for backward compatibility
        if (method_exists($callable, 'getReturnType')) {
            $returnType = $callable->getReturnType();

            if ($returnType !== null) {
                if (is_object($returnType)) {
                    if (method_exists($returnType, '__toString')) {
                        return (string) $returnType;
                    }
                    if (method_exists($returnType, 'toString')) {
                        $typeString = $returnType->toString();
                        $signatureType = $typeString === '' ? null : $typeString;
                    } elseif (method_exists($returnType, 'getTypeName')) {
                        $signatureType = $returnType->getTypeName();
                    }
                } else {
                    $signatureType = (string) $returnType;
                }
            }
        }

        if ($signatureType !== null && $signatureType !== '') {
            return $signatureType;
        }

        return null;
    }

    /**
     * Resolve version-aware type from LanguageLevelTypeAware attribute.
     *
     * Logic: Find the highest version in languageLevelTypes that is <= current PHP version.
     * If found, use that version's type. Otherwise, use default type.
     *
     * @param mixed  $callable
     * @param string $phpVersion PHP version (e.g., '8.0')
     * @return string|null Resolved type or null if no LanguageLevelTypeAware data
     */
    private function resolveVersionAwareType(mixed $callable, string $phpVersion): ?string
    {
        if (!method_exists($callable, 'getLanguageLevelTypes') || !method_exists($callable, 'getDefaultType')) {
            return null;
        }

        $languageLevelTypes = $callable->getLanguageLevelTypes();
        $defaultType = $callable->getDefaultType();

        if ($languageLevelTypes === null && $defaultType === null) {
            return null;
        }

        // Find the highest version in languageLevelTypes that is <= current PHP version
        $applicableType = null;
        $highestApplicableVersion = null;

        if (is_array($languageLevelTypes)) {
            foreach ($languageLevelTypes as $version => $type) {
                if (version_compare($phpVersion, $version, '>=')) {
                    if ($highestApplicableVersion === null || version_compare($version, $highestApplicableVersion, '>')) {
                        $highestApplicableVersion = $version;
                        $applicableType = $type;
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
            // Trim whitespace and strip leading backslashes from each part
            $parts = array_map(fn($part) => ltrim(trim($part), '\\'), $parts);
            $parts = array_unique($parts);
            sort($parts);
            $type = implode('|', $parts);
        } else {
            // For standalone types, strip leading backslash
            $type = ltrim($type, '\\');
        }

        return $type;
    }
}
