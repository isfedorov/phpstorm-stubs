<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\Entities\Model\PHPProperty;

/**
 * Validates that the declared type of properties in stubs matches reflection.
 *
 * For each class identified by $entityId the validator:
 * 1. Iterates all properties reported by reflection for the class.
 * 2. Looks up each property in the version-filtered stub hierarchy (parent classes),
 *    collecting a name → PHPProperty map with child-wins-over-parent priority.
 * 3. If the stub property is not found it is silently skipped — existence is
 *    ClassPropertiesExistCheck's responsibility.
 * 4. When both sides are found, their types are resolved and compared. For stub
 *    properties, LanguageLevelTypeAware version-specific types take effect when no
 *    explicit signature type is present.
 *
 * Type resolution priority (stubs side):
 *   1. Signature type from getType() — if non-empty, used as-is.
 *   2. LanguageLevelTypeAware — highest version <= $phpVersion wins; default type fallback.
 *
 * Special cases:
 *   - Reflection has no type but stub documents one → skip (stubs are more informative).
 *   - Both sides have no type → treated as a match.
 *   - Reflection has a type but stub declares none → reported as a failure.
 *
 * Known problems are supported at two granularities:
 * - class-level: EntityType::CLASS_TYPE + classId + 'ClassPropertiesTypeCheck'
 *   → skips all type checks for the class.
 * - property-level: EntityType::PROPERTY + '\ClassName::$propertyName' + 'ClassPropertiesTypeCheck'
 *   → skips only that specific mismatch.
 */
class ClassPropertiesTypeCheck extends AbstractPropertyFlagCheck
{
    public function supports(string $phpVersion): bool
    {
        // Typed properties were introduced in PHP 7.4
        return version_compare($phpVersion, '7.4', '>=');
    }

    protected function getCheckName(): string
    {
        return 'ClassPropertiesTypeCheck';
    }

    protected function describeMismatch(
        string $propertyEntityId,
        PHPProperty $reflProperty,
        PHPProperty $stubProperty,
        string $phpVersion
    ): ?string {
        $reflType = $this->getPropertyTypeString($reflProperty, $phpVersion);
        $stubType = $this->getPropertyTypeString($stubProperty, $phpVersion);

        // Reflection has no type but stub documents one — stub is more informative, skip
        if ($reflType === null && $stubType !== null) {
            return null;
        }

        // Both have no type — agreement, no mismatch
        if ($reflType === null && $stubType === null) {
            return null;
        }

        // Normalize both sides for semantic comparison (union order, FQN prefixes, typed arrays)
        $normalizedRefl = $this->normalizeType($reflType);
        $normalizedStub = $this->normalizeType($stubType);

        if ($normalizedRefl === $normalizedStub) {
            return null;
        }

        $stubDisplay = $stubType ?? 'undefined';
        return "Property {$propertyEntityId} type is '{$reflType}' in PHP {$phpVersion} but '{$stubDisplay}' in stubs";
    }

    /**
     * Resolve the effective type string for a property at the given PHP version.
     *
     * Priority:
     * 1. Signature type from getType() — if non-empty (not NoType), returned directly.
     * 2. LanguageLevelTypeAware — highest version entry <= $phpVersion, or defaultType.
     */
    private function getPropertyTypeString(PHPProperty $property, string $phpVersion): ?string
    {
        $sigType = $property->getType();
        if ($sigType !== null) {
            $typeString = $this->typeObjectToString($sigType);
            if ($typeString !== null && $typeString !== '') {
                return $typeString;
            }
        }

        return $this->resolveVersionAwareType($property, $phpVersion);
    }

    /**
     * Convert a type object to its string representation.
     * Returns null for NoType (whose toString() returns '') and unrecognised objects.
     */
    private function typeObjectToString(mixed $type): ?string
    {
        if (method_exists($type, '__toString')) {
            $s = (string) $type;
            return $s !== '' ? $s : null;
        }
        if (method_exists($type, 'toString')) {
            $s = $type->toString();
            return $s !== '' ? $s : null;
        }
        if (method_exists($type, 'getTypeName')) {
            $s = $type->getTypeName();
            return $s !== '' ? $s : null;
        }
        return null;
    }

    /**
     * Resolve version-aware type from LanguageLevelTypeAware attribute data.
     *
     * Finds the highest version key in languageLevelTypes that is <= $phpVersion.
     * Falls back to defaultType when no version entry applies.
     * Returns null when neither languageLevelTypes nor defaultType is set.
     */
    private function resolveVersionAwareType(PHPProperty $property, string $phpVersion): ?string
    {
        $languageLevelTypes = $property->getLanguageLevelTypes();
        $defaultType        = $property->getDefaultType();

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
     * - Converts typed-array notation (string[], int[]) to array.
     * - Strips leading backslashes from FQN class names.
     * - Sorts union-type components alphabetically so order does not matter.
     */
    private function normalizeType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $type = preg_replace('/\b(\w+)\[\]/', 'array', $type);

        if (str_contains($type, '|')) {
            $parts = explode('|', $type);
            $parts = array_map(fn($part) => ltrim(trim($part), '\\'), $parts);
            sort($parts);
            $type = implode('|', $parts);
        } else {
            $type = ltrim($type, '\\');
        }

        return $type;
    }
}
