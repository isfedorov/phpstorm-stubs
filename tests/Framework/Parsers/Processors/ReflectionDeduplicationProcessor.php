<?php

namespace StubTests\Sources\Parsers\Processors;

use StubTests\Framework\Parsers\Processors\EntityProcessor;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;

/**
 * Deduplication processor specifically for reflection data.
 *
 * Unlike the regular DeduplicationProcessor which keeps duplicates and cross-references them
 * via sourcePath, this processor filters out duplicates entirely since reflection entities
 * don't have source paths (sourcePath is null).
 *
 * This is necessary because PHP's get_declared_classes() can sometimes return the same
 * class multiple times (e.g., DOMException in PHP 8.4), and without source paths to
 * distinguish them, we need to keep only the first occurrence.
 */
class ReflectionDeduplicationProcessor implements EntityProcessor
{
    private array $seenIds = [];

    public function process($entity, array $context = [])
    {
        // Generate a unique identifier for this entity
        $id = $this->getEntityId($entity);

        if (in_array($id, $this->seenIds, true)) {
            // Already seen this entity - filter it out
            return null;
        }

        // First time seeing this entity - keep it
        $this->seenIds[] = $id;
        return $entity;
    }

    /**
     * Generate a unique identifier for an entity based on its type, name, and namespace
     */
    private function getEntityId($entity): string
    {
        // Get entity type
        $type = get_class($entity);

        // Extract name and namespace based on entity type
        $name = '';
        $namespace = '';

        if ($entity instanceof PHPClass ||
            $entity instanceof PHPInterface ||
            $entity instanceof PHPEnum ||
            $entity instanceof PHPFunction ||
            $entity instanceof PHPConstant) {
            if (method_exists($entity, 'getName')) {
                $name = $entity->getName() ?? '';
            }
            if (method_exists($entity, 'getNamespace')) {
                try {
                    $namespace = $entity->getNamespace() ?? '';
                } catch (\Error $e) {
                    // Property not initialized - use empty namespace as fallback
                    $namespace = '';
                }
            }
        }

        // Construct unique ID: type::namespace\name
        // This ensures classes, functions, constants with same name but different
        // types or namespaces are treated as distinct entities
        return $type . '::' . $namespace . '\\' . $name;
    }
}
