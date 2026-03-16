<?php

namespace StubTests\Sources\Validator;

/**
 * Provides return-type resolution helpers for FunctionReturnTypesCheck and
 * ClassMethodsReturnTypesCheck.
 *
 * Uses TypeHelperTrait for the generic resolveVersionAwareType() and normalizeType()
 * helpers and adds getReturnTypeString() which is specific to return-type validation.
 */
trait ReturnTypeHelperTrait
{
    use TypeHelperTrait;

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
        $legacyType = null;
        if (method_exists($callable, 'getReturnType')) {
            $returnType = $callable->getReturnType();

            if ($returnType !== null) {
                if (is_object($returnType)) {
                    if (method_exists($returnType, '__toString')) {
                        return (string) $returnType;
                    }
                    if (method_exists($returnType, 'toString')) {
                        $typeString = $returnType->toString();
                        $legacyType = $typeString === '' ? null : $typeString;
                    } elseif (method_exists($returnType, 'getTypeName')) {
                        $legacyType = $returnType->getTypeName();
                    }
                } else {
                    $legacyType = (string) $returnType;
                }
            }
        }

        if ($legacyType !== null && $legacyType !== '') {
            return $legacyType;
        }

        return null;
    }
}
