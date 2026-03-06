<?php

namespace StubTests\Sources\Validator;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Template;
use phpDocumentor\Reflection\DocBlock\Tags\TemplateCovariant;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;

/**
 * Shared trait for PhpDoc-vs-signature conformance checks.
 *
 * Provides the compatibility algorithm and helper methods used by
 * FunctionPhpDocConformsSignatureCheck, ClassMethodsPhpDocConformsSignatureCheck,
 * and their Enum/Interface variants.
 *
 * Requires the consuming class to also use ReturnTypeHelperTrait (which
 * provides normalizeType() and resolveVersionAwareType() via TypeHelperTrait).
 */
trait PhpDocConformanceTrait
{
    /**
     * Check if a PhpDoc type is compatible with a signature type.
     *
     * Permissive algorithm — avoids false positives from intentional patterns:
     * - Typed-array narrowing:     sig `array`, doc `string[]` → pass (string[] normalises to array)
     * - phpstan generics:          sig `array`, doc `array<K,V>` → pass (generics stripped)
     * - resource widening:         sig `GMP`,   doc `resource|GMP` → pass (intersection non-empty)
     * - bool/false split:          sig `bool`,  doc `false` → pass (bool expands to {false, true})
     * - union reordering:          sig `string|false`, doc `false|string` → pass (normalised)
     * - mixed sig or doc:          sig `mixed`, doc `string` → pass (mixed encompasses all)
     * - object sig with class doc: sig `object`, doc `SomeClass` → pass
     * - class sig with object doc: sig `SomeClass`, doc `object` → pass
     * - resource→class migration:  sig `SomeClass`, doc `resource` → pass (PHP8 object migration)
     * - @template variable in doc: sig `\SplFileInfo`, doc `\T` (T declared via @template) → pass
     * - static ↔ class name:       sig `\DateTime`, doc `static` → pass
     * - class-to-class narrowing:  sig `\Iterator`, doc `\ArrayIterator` → pass
     *
     * Catches: sig `string`, doc `int` → fail (no shared component)
     *
     * @param string $sig Signature type string (always a real PHP type — no template variables)
     * @param string $doc PhpDoc type string (may contain @template variable names)
     * @param string[] $templateNames @template variable names declared on the enclosing entity
     * @return bool true = compatible, false = mismatch detected
     */
    private function isPhpDocCompatibleWithSignature(string $sig, string $doc, array $templateNames = []): bool
    {
        $normalizedSig = $this->normalizeType($sig) ?? '';
        $normalizedDoc = $this->normalizeDocType($doc);

        if ($normalizedSig === $normalizedDoc) {
            return true;
        }

        // 'mixed' is universally compatible with any type on either side
        if ($normalizedSig === 'mixed' || $normalizedDoc === 'mixed') {
            return true;
        }

        $sigParts = $this->splitUnionComponents($normalizedSig);
        $docParts = $this->splitUnionComponents($normalizedDoc);

        // 'mixed' as a union component → compatible
        if (in_array('mixed', $sigParts) || in_array('mixed', $docParts)) {
            return true;
        }

        // 'object' compatibility: object is a supertype of all specific class types.
        // sig=object with any class doc, or class sig with doc=object → compatible.
        if ($this->isObjectCompatible($sigParts, $docParts)) {
            return true;
        }

        // Resource-to-class migration: PHP8 replaced many resource types with proper objects.
        // sig=SomeClass + doc=resource (or doc=resource|...) → intentional BC documentation.
        if ($this->isResourceToClassMigration($sigParts, $docParts)) {
            return true;
        }

        // Short-name alias: PhpDoc may use an unqualified class name (imported via `use`),
        // while the signature type has the resolved fully-qualified name.
        // sig: LDAP\Result,  doc: Result  → last segment matches → compatible
        // sig: PSpell\Config, doc: Config → last segment matches → compatible
        if ($this->isShortNameAliasCompatible($sigParts, $docParts)) {
            return true;
        }

        // @template variable in PhpDoc: template type parameters (psalm/phpstan) appear only
        // in PhpDoc and have no concrete PHP class behind them. If any PhpDoc component is a
        // declared @template variable, it is compatible with any signature type.
        // Examples: doc=\T (where T ∈ @template), doc=\TKey, doc=\TDate
        if ($this->hasTemplateType($docParts, $templateNames)) {
            return true;
        }

        // static ↔ class name: 'static' in PhpDoc or signature paired with a specific class
        // name on the other side is always valid (static means "this class or a subclass").
        // Examples: sig=\DateTime + doc=static, sig=static + doc=\DateTimeImmutable
        if ($this->isStaticClassCompatible($sigParts, $docParts)) {
            return true;
        }

        // Class-to-class narrowing: if both sides contain at least one specific class name
        // (non-primitive), the PhpDoc may be intentionally narrowing the type to a subtype.
        // We cannot check class hierarchy here, so we accept all class-to-class pairs.
        // Examples: sig=\Iterator + doc=\ArrayIterator, sig=\SplFileInfo + doc=\PharFileInfo
        if ($this->isBothSidesClassTypes($sigParts, $docParts)) {
            return true;
        }

        // Expand bool ↔ {false, true} in both sets so that
        // sig: bool is compatible with doc: false (and vice versa)
        $sigExpanded = $this->expandBool($sigParts);
        $docExpanded = $this->expandBool($docParts);

        return !empty(array_intersect($sigExpanded, $docExpanded));
    }

    /**
     * Extract @template variable names from a raw PhpDoc comment.
     *
     * Uses phpDocumentor's DocBlockFactory to parse @template tags and retrieve
     * the declared type parameter names (e.g., @template T, @template TKey → ['T', 'TKey']).
     *
     * @return string[] Template variable names (without any leading backslash)
     */
    private function extractTemplateNames(?string $rawPhpDoc): array
    {
        if ($rawPhpDoc === null || trim($rawPhpDoc) === '') {
            return [];
        }

        try {
            $factory = DocBlockFactory::createInstance();
            $docBlock = $factory->create($rawPhpDoc);
        } catch (\Exception $e) {
            return [];
        }

        $names = [];
        foreach ($docBlock->getTagsByName('template') as $tag) {
            if ($tag instanceof Template) {
                $names[] = $tag->getTemplateName();
            }
        }

        foreach ($docBlock->getTagsByName('template-covariant') as $tag) {
            if ($tag instanceof TemplateCovariant) {
                $typeName = ltrim((string) $tag->getType(), '\\');
                if ($typeName !== '') {
                    $names[] = $typeName;
                }
            }
        }

        return $names;
    }

    /**
     * Normalise a PhpDoc type string.
     *
     * Strips phpstan/psalm annotations first, then applies standard
     * normalizeType() (union ordering, FQN backslash, T[] → array).
     */
    private function normalizeDocType(string $type): string
    {
        $type = $this->stripPhpStanGenerics($type);
        return $this->normalizeType($type) ?? '';
    }

    /**
     * Strip phpstan/psalm generic annotations from a type string.
     *
     * Handles:
     * - callable(T): T      → callable
     * - ?T                  → T|null (PHP nullable shorthand in PhpDoc)
     * - non-empty-X         → X
     * - positive-X          → X  (e.g. positive-int → int)
     * - non-negative-X      → X
     * - T<...>              → T  (generics stripped iteratively for nesting)
     * - array{...}          → array  (array shapes stripped iteratively)
     * - list<T> / list      → array
     * - class-string[<T>]   → string
     * - T[]                 → array (handled by normalizeType)
     */
    private function stripPhpStanGenerics(string $type): string
    {
        // callable(...)...: T → callable (must run before generic stripping)
        $type = preg_replace('/\bcallable\s*\([^)]*\)(\s*:\s*\S+)?/', 'callable', $type);

        // ?T → T|null (PHP nullable shorthand sometimes used in PhpDoc)
        // Only process simple ?T (no union) to avoid ambiguity
        if (str_starts_with($type, '?') && !str_contains($type, '|')) {
            $type = substr($type, 1) . '|null';
        }

        // Strip phpstan type prefixes: non-empty-, positive-, non-negative-, non-positive-, etc.
        $type = preg_replace('/\b(?:non-empty|positive|non-negative|non-positive)-/', '', $type);

        // Strip generics <...> iteratively to handle nesting:
        //   array<string, array<int, bool>> → array<string, array> → array
        $prev = null;
        while ($prev !== $type) {
            $prev = $type;
            $type = preg_replace('/<[^<>]*>/', '', $type);
        }

        // Strip array shapes {...} iteratively to handle nesting:
        //   array{key: array{a: int}} → array{key: array} → array
        $prev = null;
        while ($prev !== $type) {
            $prev = $type;
            $type = preg_replace('/\{[^{}]*\}/', '', $type);
        }

        // Replace bare 'list' (possibly remaining after generic stripping) with 'array'
        $type = preg_replace('/\blist\b/', 'array', $type);

        // class-string (after generic stripping removes <T>) → string
        $type = preg_replace('/\bclass-string\b/', 'string', $type);

        return trim($type);
    }

    /**
     * Split a union type string into individual components.
     *
     * @return string[]
     */
    private function splitUnionComponents(string $type): array
    {
        if (!str_contains($type, '|')) {
            return [$type];
        }
        return array_map('trim', explode('|', $type));
    }

    /**
     * PHP primitive types — used to distinguish class names from scalar/pseudo types.
     */
    private const PRIMITIVES = [
        'array', 'bool', 'callable', 'false', 'float', 'int', 'iterable',
        'mixed', 'never', 'null', 'object', 'resource', 'self', 'parent',
        'static', 'string', 'true', 'void',
    ];

    /**
     * Check if an 'object' type on one side is compatible with specific class types on the other.
     *
     * The PHP 'object' type is a supertype of all specific class instances.
     * sig=object with doc=SomeClass and vice versa should both pass.
     *
     * @param string[] $sigParts
     * @param string[] $docParts
     */
    private function isObjectCompatible(array $sigParts, array $docParts): bool
    {
        $hasObjectInSig = in_array('object', $sigParts);
        $hasObjectInDoc = in_array('object', $docParts);

        if (!$hasObjectInSig && !$hasObjectInDoc) {
            return false;
        }

        $hasNonPrimitiveInSig = !empty(array_diff($sigParts, self::PRIMITIVES));
        $hasNonPrimitiveInDoc = !empty(array_diff($docParts, self::PRIMITIVES));

        // sig=object: compatible with any specific class doc (or another object)
        if ($hasObjectInSig && ($hasNonPrimitiveInDoc || $hasObjectInDoc)) {
            return true;
        }

        // doc=object: compatible with any specific class sig (or object sig)
        if ($hasObjectInDoc && ($hasNonPrimitiveInSig || $hasObjectInSig)) {
            return true;
        }

        return false;
    }

    /**
     * Detect resource-to-class migrations (PHP 8 replaced many resource types with objects).
     *
     * Handles two directions:
     * Forward (PhpDoc not updated):
     *   sig=SomeClass + doc=resource → pass (the PhpDoc was not updated for PHP 8+)
     * Reverse (sig uses LanguageLevelTypeAware, resolves to 'resource' for old PHP versions):
     *   sig=resource + doc=SomeClass → pass (the signature resolves to resource for PHP 5/7)
     *
     * Examples:
     * - sig: Dba\Connection, doc: resource             → pass (forward)
     * - sig: PgSql\Connection, doc: resource           → pass (forward)
     * - sig: resource, doc: \SysvSharedMemory          → pass (reverse, PHP<8.0 via LLA)
     * - sig: resource, doc: \OpenSSLAsymmetricKey      → pass (reverse)
     * - sig: FTP\Connection, doc: FTP\Connection|resource → already passes via intersection
     *
     * @param string[] $sigParts
     * @param string[] $docParts
     */
    private function isResourceToClassMigration(array $sigParts, array $docParts): bool
    {
        // Forward direction: sig=class, doc=resource
        if (in_array('resource', $docParts)) {
            $sigClasses = array_diff($sigParts, self::PRIMITIVES);
            $docClasses = array_diff($docParts, self::PRIMITIVES);
            if (!empty($sigClasses) && empty($docClasses)) {
                return true;
            }
        }

        // Reverse direction: sig=resource, doc=class
        if (in_array('resource', $sigParts)) {
            $docClasses = array_diff($docParts, self::PRIMITIVES);
            $sigClasses = array_diff($sigParts, self::PRIMITIVES);
            if (!empty($docClasses) && empty($sigClasses)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect use-statement alias compatibility.
     *
     * PhpDoc types are not resolved through `use` statements, so a stub file that has
     * `use LDAP\Result` and writes `@param resource|Result $x` will produce a doc type
     * of 'Result', while the parsed signature type is the FQN 'LDAP\Result'.
     *
     * This check accepts the pair when one side is an unqualified short name that
     * exactly matches the last segment (after the last `\`) of the other side's
     * qualified name.
     *
     * Examples:
     *  sig: LDAP\Result,   doc: Result   → last segment of sig = "Result" = doc → pass
     *  sig: PSpell\Config, doc: Config   → last segment of sig = "Config" = doc → pass
     *
     * Only one direction is needed: signature types are always FQN-resolved by the
     * parser; PhpDoc types are never resolved.
     *
     * @param string[] $sigParts
     * @param string[] $docParts
     */
    private function isShortNameAliasCompatible(array $sigParts, array $docParts): bool
    {
        foreach ($sigParts as $sig) {
            if (!str_contains($sig, '\\')) {
                continue; // Already unqualified — no alias ambiguity
            }
            $shortSig = substr($sig, strrpos($sig, '\\') + 1);
            foreach ($docParts as $doc) {
                if ($doc === $shortSig) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check whether any PhpDoc type component is a declared @template variable.
     *
     * Template type parameters (like T, TKey, TValue) only appear in PhpDoc, never in
     * actual PHP signatures. A PhpDoc type that is a template variable is compatible with
     * any signature type.
     *
     * The check strips any leading backslash before comparing against the known names,
     * since phpDocumentor produces \TKey but @template declarations use TKey.
     *
     * @param string[] $docParts  Normalised components of the PhpDoc union type
     * @param string[] $templateNames  Names from @template tags of the enclosing entity
     */
    private function hasTemplateType(array $docParts, array $templateNames): bool
    {
        if (empty($templateNames)) {
            return false;
        }

        foreach ($docParts as $part) {
            $bare = ltrim($part, '\\');
            if (in_array($bare, $templateNames, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check compatibility between 'static' on one side and a specific class name on the other.
     *
     * 'static' in PHP means "an instance of the called class (or a subclass)".
     * A PhpDoc that uses 'static' where the signature has a class name (or vice versa)
     * is a valid narrowing/widening pattern.
     *
     * Examples:
     *   sig: \DateTime,   doc: static         → compatible
     *   sig: static,      doc: \DateTime       → compatible
     *   sig: \DateTime,   doc: static|false    → compatible
     *
     * @param string[] $sigParts
     * @param string[] $docParts
     */
    private function isStaticClassCompatible(array $sigParts, array $docParts): bool
    {
        $sigHasStatic = in_array('static', $sigParts);
        $docHasStatic = in_array('static', $docParts);

        if (!$sigHasStatic && !$docHasStatic) {
            return false;
        }

        // If doc has 'static', check that sig has a non-primitive class name
        if ($docHasStatic && !empty(array_diff($sigParts, self::PRIMITIVES))) {
            return true;
        }

        // If sig has 'static', check that doc has a non-primitive class name
        if ($sigHasStatic && !empty(array_diff($docParts, self::PRIMITIVES))) {
            return true;
        }

        return false;
    }

    /**
     * Detect class-to-class subtype narrowing.
     *
     * When both sides contain at least one specific class name (non-primitive), the PhpDoc
     * may be intentionally narrowing the return/parameter type to a subclass or a more
     * specific implementation. Since we have no class hierarchy info here, we accept all
     * such pairs to avoid false positives.
     *
     * Examples:
     *   sig: \Iterator,                     doc: \ArrayIterator              → compatible
     *   sig: \SplFileInfo,                  doc: \PharFileInfo               → compatible
     *   sig: \RecursiveFilterIterator|null, doc: \ParentIterator             → compatible
     *   sig: Traversable,                   doc: \RecursiveIterator|\IteratorAggregate → compatible
     *
     * @param string[] $sigParts
     * @param string[] $docParts
     */
    private function isBothSidesClassTypes(array $sigParts, array $docParts): bool
    {
        return !empty(array_diff($sigParts, self::PRIMITIVES))
            && !empty(array_diff($docParts, self::PRIMITIVES));
    }

    /**
     * Expand 'bool' into 'false' and 'true' in a component set.
     * This allows sig: bool to be compatible with doc: false or true.
     *
     * @param string[] $parts
     * @return string[]
     */
    private function expandBool(array $parts): array
    {
        $expanded = [];
        foreach ($parts as $part) {
            $expanded[] = $part;
            if ($part === 'bool') {
                $expanded[] = 'false';
                $expanded[] = 'true';
            }
        }
        return $expanded;
    }

    /**
     * Get the signature type string for a parameter.
     *
     * Priority:
     * 1. Declared type from getDeclaredType() — if non-empty (not NoType)
     * 2. LanguageLevelTypeAware — highest version <= $phpVersion, or defaultType
     */
    private function getParamSigTypeForPhpDoc(PHPParameter $param, string $phpVersion): ?string
    {
        $declaredType = $param->getDeclaredType();

        $typeString = '';
        if (method_exists($declaredType, '__toString')) {
            $typeString = (string) $declaredType;
        } elseif (method_exists($declaredType, 'toString')) {
            $typeString = $declaredType->toString();
        }

        if ($typeString !== '') {
            return $typeString;
        }

        // No signature type → try LanguageLevelTypeAware
        $versionAwareType = $this->resolveVersionAwareType($param, $phpVersion);
        if ($versionAwareType !== null && $versionAwareType !== '') {
            return $versionAwareType;
        }

        return null;
    }

    /**
     * Get the signature type string for a property.
     *
     * Priority:
     * 1. Signature type from getType() — if non-empty (not NoType)
     * 2. LanguageLevelTypeAware — highest version <= $phpVersion, or defaultType
     */
    private function getPropertySigTypeForPhpDoc(PHPProperty $property, string $phpVersion): ?string
    {
        $type = $property->getType();

        if ($type !== null) {
            $typeString = '';
            if (method_exists($type, '__toString')) {
                $typeString = (string) $type;
            } elseif (method_exists($type, 'toString')) {
                $typeString = $type->toString();
            }

            if ($typeString !== '') {
                return $typeString;
            }
        }

        // No signature type → try LanguageLevelTypeAware
        $versionAwareType = $this->resolveVersionAwareType($property, $phpVersion);
        if ($versionAwareType !== null && $versionAwareType !== '') {
            return $versionAwareType;
        }

        return null;
    }

    /**
     * Filter parameters by version availability and deduplicate same-named variadic pairs.
     *
     * Mirrors AbstractMethodFlagCheck::filterAndDeduplicateParams(), duplicated here
     * because PhpDocConformsSignatureCheck extends AbstractClassCheck (not AbstractMethodFlagCheck)
     * and FunctionPhpDocConformsSignatureCheck extends AbstractCallableCheck.
     *
     * @param PHPParameter[] $params
     * @return PHPParameter[]
     */
    private function filterAndDeduplicateParamsPhpDoc(array $params, string $phpVersion): array
    {
        $filtered = [];
        foreach ($params as $param) {
            $since   = $param->getSinceVersion();
            $removed = $param->getRemovedVersion();

            $available = ($since === null || version_compare($phpVersion, $since, '>='))
                && ($removed === null || version_compare($phpVersion, $removed, '<'));

            if ($available) {
                $filtered[] = $param;
            }
        }

        // Merge consecutive same-named params where the second is variadic
        $merged = [];
        $count  = count($filtered);
        for ($i = 0; $i < $count; $i++) {
            $current = $filtered[$i];
            $next    = $filtered[$i + 1] ?? null;

            if ($next !== null
                && $current->getName() === $next->getName()
                && $next->isVariadic()
            ) {
                $merged[] = $next;
                $i++;
            } else {
                $merged[] = $current;
            }
        }

        return $merged;
    }
}
