<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;

/**
 * Validates that return types in stubs match those in reflection.
 *
 * The entityId should be in format: "FunctionName" or "ClassName::methodName"
 */
class ReturnTypesCheck implements CheckInterface
{
    private ReflectionProviderInterface $reflectionProvider;
    private KnownProblemsRegistry $knownProblemsRegistry;
    private ?string $currentPhpVersion = null;

    /**
     * @param ReflectionProviderInterface|null $reflectionProvider Optional reflection provider for dependency injection.
     *                                                              Defaults to RunnerReflectionProvider for production use.
     * @param KnownProblemsRegistry|null $knownProblemsRegistry Optional registry for known problems.
     *                                                            Defaults to singleton instance.
     */
    public function __construct(
        ?ReflectionProviderInterface $reflectionProvider = null,
        ?KnownProblemsRegistry $knownProblemsRegistry = null
    ) {
        $this->reflectionProvider = $reflectionProvider ?? new RunnerReflectionProvider();
        $this->knownProblemsRegistry = $knownProblemsRegistry ?? KnownProblemsRegistry::getInstance();
    }

    public function supports(string $phpVersion): bool
    {
        // Return type declarations were introduced in PHP 7.0
        return version_compare($phpVersion, '7.0', '>=');
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        // Store PHP version for use in findCallable
        $this->currentPhpVersion = $phpVersion;

        // Check if this entity has a known problem that should skip validation
        $entityType = str_contains($entityId, '::') ? 'methods' : 'functions';
        if ($this->knownProblemsRegistry->shouldSkipValidation(
            $entityType,
            $entityId,
            'ReturnTypesCheck',
            $phpVersion
        )) {
            $reason = $this->knownProblemsRegistry->getSkipReason(
                $entityType,
                $entityId,
                'ReturnTypesCheck',
                $phpVersion
            );
            // Mark as success with note that validation was skipped
            $results->addSuccess($entityId . ' (skipped: ' . $reason . ')');
            return $results;
        }

        // Get the function/method from reflection
        $reflection = $this->reflectionProvider->getReflection($phpVersion);
        $reflectionCallable = $this->findCallable($reflection, $entityId);

        if ($reflectionCallable === null) {
            $results->addFailure(
				$entityId,
	            "Function/method {$entityId} not found in reflection data"
            );
            return $results;
        }

        // Get the function/method from stubs
        $stubCallable = $this->findCallable($stubs, $entityId);

        if ($stubCallable === null) {
            $results->addFailure(
				$entityId,
	            "Function/method {$entityId} not found in stubs"
            );
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
     * Both reflection and stubs now return FQN (e.g., 'Dom\Attr'), and we just need to ensure
     * consistent handling of leading backslashes for comparison.
     *
     * @param string|null $type Type string to normalize, or null if no type information
     * @return string|null Normalized type string, or null if input was null
     */
    private function normalizeType(?string $type): ?string
    {
        // If type is null (no type information), return null
        if ($type === null) {
            return null;
        }
        // Handle typed arrays: convert string[], int[], etc. to array
        // Pattern: word[] (but not if it's part of a longer word)
        $type = preg_replace('/\b(\w+)\[\]/', 'array', $type);

        // Handle union types: sort components for consistent comparison
        if (str_contains($type, '|')) {
            $parts = explode('|', $type);
            // Trim whitespace and strip leading backslashes from each part
            $parts = array_map(fn($part) => ltrim(trim($part), '\\'), $parts);
            // Sort alphabetically
            sort($parts);
            // Rejoin
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
     * @return string|null Returns null when no return type information is available (not the same as 'mixed')
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
                        // NoType returns empty string
                        $signatureType = $typeString === '' ? null : $typeString;
                    } elseif (method_exists($returnType, 'getTypeName')) {
                        $signatureType = $returnType->getTypeName();
                    }
                } else {
                    $signatureType = (string) $returnType;
                }
            }
        }

        // If we have a signature type, use it (highest priority)
        if ($signatureType !== null && $signatureType !== '') {
            return $signatureType;
        }

        // Check for LanguageLevelTypeAware (second priority)
        // Only used when there's no explicit signature type
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
                        // NoType returns empty string
                        $signatureType = $typeString === '' ? null : $typeString;
                    } elseif (method_exists($returnType, 'getTypeName')) {
                        $signatureType = $returnType->getTypeName();
                    }
                } else {
                    $signatureType = (string) $returnType;
                }
            }
        }

        // If we have a signature type, use it (highest priority)
        if ($signatureType !== null && $signatureType !== '') {
            return $signatureType;
        }

        // No return type information available
        // Note: This is different from 'mixed' type - it means the reflection
        // doesn't provide any return type information (e.g., PHP 7.x, or PHP 8.0
        // functions that haven't been updated with return types yet)
        return null;
    }

    /**
     * Resolve version-aware type from LanguageLevelTypeAware attribute.
     *
     * Logic: Find the highest version in languageLevelTypes that is <= current PHP version.
     * If found, use that version's type. Otherwise, use default type.
     *
     * Example: ['8.4' => 'true'], default: 'bool'
     * - PHP 8.0: No version <= 8.0, use default → 'bool'
     * - PHP 8.4: Found '8.4' <= 8.4, use that → 'true'
     * - PHP 8.5: Found '8.4' <= 8.5, use that → 'true'
     *
     * @param mixed $callable
     * @param string $phpVersion PHP version (e.g., '8.0')
     * @return string|null Resolved type or null if no LanguageLevelTypeAware data
     */
    private function resolveVersionAwareType($callable, string $phpVersion): ?string
    {
        // Check if callable has LanguageLevelTypeAware data
        if (!method_exists($callable, 'getLanguageLevelTypes') || !method_exists($callable, 'getDefaultType')) {
            return null;
        }

        $languageLevelTypes = $callable->getLanguageLevelTypes();
        $defaultType = $callable->getDefaultType();

        // No LanguageLevelTypeAware data
        if ($languageLevelTypes === null && $defaultType === null) {
            return null;
        }

        // Find the highest version in languageLevelTypes that is <= current PHP version
        $applicableType = null;
        $highestApplicableVersion = null;

        if (is_array($languageLevelTypes)) {
            foreach ($languageLevelTypes as $version => $type) {
                // Check if this version applies to current PHP version
                if (version_compare($phpVersion, $version, '>=')) {
                    // This version applies - check if it's the highest one we've seen
                    if ($highestApplicableVersion === null || version_compare($version, $highestApplicableVersion, '>')) {
                        $highestApplicableVersion = $version;
                        $applicableType = $type;
                    }
                }
            }
        }

        // If we found an applicable version-specific type, use it
        if ($applicableType !== null) {
            return $applicableType;
        }

        // Otherwise, use default type
        return $defaultType;
    }

    /**
     * Find a function or method in the given storage.
     *
     * @param ParsedDataStorageManager $storage
     * @param string $functionOrMethodId Format: "functionName" or "ClassName::methodName"
     * @return mixed|null The function/method object or null if not found
     */
    private function findCallable(ParsedDataStorageManager $storage, string $functionOrMethodId)
    {
        // Check if it's a method (contains ::)
        if (str_contains($functionOrMethodId, '::')) {
            [$className, $methodName] = explode('::', $functionOrMethodId, 2);

            // Find the class
            $classes = $storage->getClasses();
            foreach ($classes as $class) {
                $classId = method_exists($class, 'getId') ? $class->getId() :
                    (method_exists($class, 'getName') ? $class->getName() : '');

                if ($classId === $className) {
                    // Find the method in this class
                    if (method_exists($class, 'getMethods')) {
                        $methods = $class->getMethods();
                        foreach ($methods as $method) {
                            $stubMethodName = method_exists($method, 'getName') ? $method->getName() : '';
                            if ($stubMethodName === $methodName) {
                                return $method;
                            }
                        }
                    }
                }
            }
            return null;
        }

        // It's a function - may have multiple versions
        return $this->findVersionedFunction($storage, $functionOrMethodId);
    }

    /**
     * Find a function in storage, considering version availability.
     *
     * Some functions have multiple definitions with different #[PhpStormStubsElementAvailable]
     * attributes. This method finds the function definition that's available for the current
     * PHP version being validated.
     *
     * @param ParsedDataStorageManager $storage
     * @param string $functionId Function identifier
     * @return mixed|null The function object or null if not found
     */
    private function findVersionedFunction(ParsedDataStorageManager $storage, string $functionId)
    {
        $functions = $storage->getFunctions();
        $candidates = [];

        // Find all functions matching the ID
        foreach ($functions as $function) {
            $currentId = method_exists($function, 'getId') ? $function->getId() :
                (method_exists($function, 'getName') ? $function->getName() : '');

            if ($currentId === $functionId) {
                $candidates[] = $function;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // If only one candidate, return it
        if (count($candidates) === 1) {
            return $candidates[0];
        }

        // Multiple candidates - filter by version availability
        $phpVersion = $this->currentPhpVersion;

        if ($phpVersion === null) {
            // No version context, return first candidate
            return $candidates[0];
        }

        // Filter by version availability
        foreach ($candidates as $candidate) {
            $sinceVersion = method_exists($candidate, 'getSinceVersion') ? $candidate->getSinceVersion() : null;
            $removedVersion = method_exists($candidate, 'getRemovedVersion') ? $candidate->getRemovedVersion() : null;

            $isAvailableSince = $sinceVersion === null || version_compare($phpVersion, $sinceVersion, '>=');
            $isNotRemoved = $removedVersion === null || version_compare($phpVersion, $removedVersion, '<=');

            if ($isAvailableSince && $isNotRemoved) {
                return $candidate;
            }
        }

        // No candidate matches the version, return first one
        return $candidates[0];
    }
}
