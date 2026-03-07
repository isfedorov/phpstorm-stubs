<?php

namespace StubTests\Sources\Validator\PhpDoc;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that PhpDoc comments contain only recognized tag names.
 *
 * Valid tags are those from phpDocumentor v3, PHPStan (non-prefixed forms only),
 * and a small set of custom tags used in phpstorm-stubs.
 *
 * Tags with phpstan-*, psalm-*, or phan-* prefixes are explicitly invalid.
 * Any tag not in the whitelist is also flagged.
 *
 * For class-like entities (classes, interfaces, enums) the check examines:
 * - the entity-level phpDoc
 * - the phpDoc of every declared method
 *
 * For functions the check examines the function-level phpDoc only.
 *
 * Known problems are supported at entity level:
 * - entityType + entityId + 'PhpDocTagsCheck' → skips all tag checks for the entity.
 */
class PhpDocTagsCheck extends AbstractReflectionCheck
{
    private const VALID_TAGS = [
        // phpDocumentor v3 standard tags
        'api', 'author', 'category', 'copyright', 'deprecated', 'example',
        'filesource', 'global', 'ignore', 'inheritdoc', 'internal', 'license',
        'link', 'method', 'mixin', 'package', 'param', 'property',
        'property-read', 'property-write', 'return', 'see', 'since', 'source',
        'subpackage', 'throws', 'todo', 'uses', 'used-by', 'var', 'version',
        // PHPStan non-prefixed tags (template system, type-level contracts)
        'template', 'template-covariant', 'template-contravariant',
        'template-implements', 'template-extends',
        'extends', 'implements', 'use',
        'require-extends', 'require-implements',
        'immutable', 'readonly', 'pure', 'impure',
        'consistent-constructor', 'no-named-arguments',
        'param-out', 'type',
        'assert', 'assert-if-true', 'assert-if-false',
        // Custom tags used in phpstorm-stubs
        'removed',  // marks when an entity was removed from PHP
        'xglobal',  // documents PHP global variables
        'meta',     // PhpStorm metadata hint
        'public',   // accessibility marker (pq extension stubs)
    ];

    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        $found = $this->findEntityById($stubs, $entityId);
        if (empty($found)) {
            $results->addSuccess($entityId);
            return $results;
        }

        [$entity, $entityType] = $found;

        if ($this->skipWithKnownProblem($results, $entityType->value, $entityId, 'PhpDocTagsCheck', $phpVersion)) {
            return $results;
        }

        $invalidTagsByLocation = $this->collectInvalidTagsByLocation($entity, $entityId);

        if (empty($invalidTagsByLocation)) {
            $results->addSuccess($entityId);
            return $results;
        }

        foreach ($invalidTagsByLocation as $location => $tags) {
            $results->addFailure(
                $location,
                "{$location} PhpDoc contains invalid tags in PHP {$phpVersion}: @" . implode(', @', $tags)
            );
        }

        return $results;
    }

    /**
     * Collect invalid tags across the entity phpDoc and all method phpDocs.
     *
     * @return array<string, string[]> Map of location (entityId or methodId) → invalid tag names
     */
    private function collectInvalidTagsByLocation(object $entity, string $entityId): array
    {
        $result = [];

        // Entity-level phpDoc
        if (method_exists($entity, 'getPhpDoc')) {
            $phpDoc = $entity->getPhpDoc();
            if ($phpDoc !== null && $phpDoc !== '') {
                $invalid = $this->findInvalidTagNames($phpDoc);
                if (!empty($invalid)) {
                    $result[$entityId] = $invalid;
                }
            }
        }

        // Method-level phpDocs for class-like entities
        if (method_exists($entity, 'getMethods')) {
            foreach ($entity->getMethods() as $method) {
                if (!method_exists($method, 'getPhpDoc') || !method_exists($method, 'getName')) {
                    continue;
                }
                $phpDoc = $method->getPhpDoc();
                if ($phpDoc === null || $phpDoc === '') {
                    continue;
                }
                $invalid = $this->findInvalidTagNames($phpDoc);
                if (!empty($invalid)) {
                    $methodId = $entityId . '::' . $method->getName();
                    $result[$methodId] = $invalid;
                }
            }
        }

        return $result;
    }

    /**
     * Return tag names present in $phpDoc that are not in the whitelist.
     *
     * @return string[] Sorted, deduplicated list of invalid lowercase tag names
     */
    private function findInvalidTagNames(string $phpDoc): array
    {
        $invalid = [];
        foreach ($this->extractTagNames($phpDoc) as $tag) {
            if (!in_array($tag, self::VALID_TAGS, true)) {
                $invalid[] = $tag;
            }
        }
        $invalid = array_unique($invalid);
        sort($invalid);
        return $invalid;
    }

    /**
     * Extract all distinct lowercase tag names from a phpDoc string.
     *
     * Matches:
     * - Block tags: lines of the form <whitespace>*<whitespace>@tagname
     * - Inline tags: {@tagname}
     *
     * Does NOT match @-signs inside email addresses or URLs because those are
     * not at the start of a phpDoc comment line and are not wrapped in {}.
     *
     * @return string[]
     */
    private function extractTagNames(string $phpDoc): array
    {
        $tags = [];

        // Block tags (line starts with optional whitespace, *, optional whitespace, then @tag)
        preg_match_all('/^\s*\*\s+@([a-zA-Z][a-zA-Z0-9_-]*)/m', $phpDoc, $blockMatches);
        foreach ($blockMatches[1] as $tag) {
            $tags[] = strtolower($tag);
        }

        // Inline tags: {@tagname}
        preg_match_all('/\{@([a-zA-Z][a-zA-Z0-9_-]*)\}/', $phpDoc, $inlineMatches);
        foreach ($inlineMatches[1] as $tag) {
            $tags[] = strtolower($tag);
        }

        return array_unique($tags);
    }

    /**
     * Find an entity by ID across all entity collections in the storage.
     *
     * @return array{0: object, 1: EntityType}|array{} Pair [entity, entityType], or empty array if not found
     */
    private function findEntityById(ParsedDataStorageManager $stubs, string $entityId): array
    {
        foreach ($stubs->getClasses() as $class) {
            if (method_exists($class, 'getId') && $class->getId() === $entityId) {
                return [$class, EntityType::CLASS_TYPE];
            }
        }

        foreach ($stubs->getInterfaces() as $interface) {
            if (method_exists($interface, 'getId') && $interface->getId() === $entityId) {
                return [$interface, EntityType::INTERFACE_TYPE];
            }
        }

        foreach ($stubs->getEnums() as $enum) {
            if (method_exists($enum, 'getId') && $enum->getId() === $entityId) {
                return [$enum, EntityType::ENUM_TYPE];
            }
        }

        foreach ($stubs->getFunctions() as $function) {
            $id = method_exists($function, 'getId') ? $function->getId() : null;
            $name = method_exists($function, 'getName') ? $function->getName() : null;
            if ($id === $entityId || $name === $entityId) {
                return [$function, EntityType::FUNCTION];
            }
        }

        return [];
    }
}
