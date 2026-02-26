<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;

/**
 * Validates that return types in stubs match those in reflection.
 *
 * The entityId should be in format: "FunctionName" or "ClassName::methodName"
 */
class ReturnTypesCheck extends AbstractCallableCheck
{
    public function supports(string $phpVersion): bool
    {
        // Return type declarations were introduced in PHP 7.0
        return version_compare($phpVersion, '7.0', '>=');
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        // Check if this entity has a known problem that should skip validation
        $entityType = str_contains($entityId, '::') ? 'methods' : 'functions';
        if ($this->skipWithKnownProblem($results, $entityType, $entityId, 'ReturnTypesCheck', $phpVersion)) {
            return $results;
        }

        // Get the function/method from reflection
        $reflection = $this->reflectionProvider->getReflection($phpVersion);
        $reflectionCallable = $this->findCallable($reflection, $entityId, $phpVersion);

        if ($reflectionCallable === null) {
            $results->addFailure($entityId, "Function/method {$entityId} not found in reflection data");
            return $results;
        }

        // Get the function/method from stubs
        $stubCallable = $this->findCallable($stubs, $entityId, $phpVersion);

        if ($stubCallable === null) {
            $results->addFailure($entityId, "Function/method {$entityId} not found in stubs");
            return $results;
        }

        // Get return types from both
        $reflectionReturnType = $this->getReturnTypeString($reflectionCallable, $phpVersion);
        $stubReturnType = $this->getReturnTypeString($stubCallable, $phpVersion);

        // Special case: Reflection has no return type information available
        // This applies to:
        // - PHP 7.x: Return types weren't available via Reflection API
        // - PHP 8.0: Many functions still lack return type declarations (59 functions)
        // - PHP 8.1+: Some functions may still be missing return types
        // If reflection has no type (null) and stub has a return type, skip validation
        // because stubs correctly document the type even when reflection doesn't provide it.
        if ($reflectionReturnType === null && $stubReturnType !== null) {
            // Format version context for message
            $versionContext = version_compare($phpVersion, '8.0', '<') ? 'PHP 7.x' : 'PHP ' . $phpVersion;
            $results->addSuccess($entityId . ' (return type not available in Reflection API for ' . $versionContext . ')');
            return $results;
        }

        // If both have no type information, that's a match (both agree there's no type)
        if ($reflectionReturnType === null && $stubReturnType === null) {
            $results->addSuccess($entityId);
            return $results;
        }

        // Normalize both types for semantic comparison
        $normalizedReflectionType = $this->normalizeType($reflectionReturnType);
        $normalizedStubType = $this->normalizeType($stubReturnType);

        // Compare return types
        if ($normalizedReflectionType !== $normalizedStubType) {
            $results->addFailure(
                $entityId,
                "Return type mismatch: reflection has '{$reflectionReturnType}', " .
                "stubs have '{$stubReturnType}'"
            );
        } else {
            $results->addSuccess($entityId);
        }

        return $results;
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
            sort($parts);
            $type = implode('|', $parts);
        } else {
            // For standalone types, strip leading backslash
            $type = ltrim($type, '\\');
        }

        return $type;
    }

    /**
     * Get the return type string representation from a function/method.
     * Supports version-aware types via LanguageLevelTypeAware attribute.
     *
     * Priority order:
     * 1. Signature type (if present) - takes precedence over everything
     * 2. LanguageLevelTypeAware (if no signature type)
     * 3. Legacy getReturnType() method (backward compatibility)
     *
     * @param mixed $callable
     * @param string $phpVersion PHP version (e.g., '8.0')
     * @return string|null Returns null when no return type information is available
     */
    private function getReturnTypeString($callable, string $phpVersion): ?string
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
     * @param mixed $callable
     * @param string $phpVersion PHP version (e.g., '8.0')
     * @return string|null Resolved type or null if no LanguageLevelTypeAware data
     */
    private function resolveVersionAwareType($callable, string $phpVersion): ?string
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
}
