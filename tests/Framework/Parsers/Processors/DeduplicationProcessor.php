<?php

namespace StubTests\Sources\Parsers\Processors;

use StubTests\Sources\Model\Entities\PHPClass;
use StubTests\Sources\Model\Entities\PHPConstant;
use StubTests\Sources\Model\Entities\PHPEnum;
use StubTests\Sources\Model\Entities\PHPFunction;
use StubTests\Sources\Model\Entities\PHPInterface;
use StubTests\Sources\Parsers\EntityProcessor;

class DeduplicationProcessor implements EntityProcessor
{
    private array $seenEntities = [];

    public function process($entity, array $context = [])
    {
        // Check against entities we've already processed and kept
        foreach ($this->seenEntities as $existing) {
            if ($this->isDuplicate($entity, $existing)) {
                // Found duplicate - create bidirectional cross-reference
                $duplicateSourcePath = $entity->getSourcePath();
                $existingSourcePath = $existing->getSourcePath();

                if ($duplicateSourcePath !== null) {
                    // Add current entity's path to existing entity's duplicates
                    $existing->addDuplicate($duplicateSourcePath);
                }

                if ($existingSourcePath !== null) {
                    // Add existing entity's path to current entity's duplicates
                    $entity->addDuplicate($existingSourcePath);
                }

                // KEEP the duplicate entity (don't filter it out)
                // This allows JSON to contain ALL versions
                break;  // Stop checking after first match
            }
        }

        // Add to seen entities (whether duplicate or not)
        $this->seenEntities[] = $entity;

        return $entity;
    }

    private function isDuplicate($entity1, $entity2): bool
    {
        // Must be same type
        if (get_class($entity1) !== get_class($entity2)) {
            return false;
        }

        // Generic approach: check if both have getName() and getNamespace() methods
        if (method_exists($entity1, 'getName') &&
            method_exists($entity2, 'getName') &&
            method_exists($entity1, 'getNamespace') &&
            method_exists($entity2, 'getNamespace')) {
            return $entity1->getName() === $entity2->getName() &&
                   $entity1->getNamespace() === $entity2->getNamespace();
        }

        // Specific type checks for entities without standard methods
        if ($entity1 instanceof PHPClass && $entity2 instanceof PHPClass) {
            return $entity1->getName() === $entity2->getName() &&
                   $entity1->getNamespace() === $entity2->getNamespace();
        }

        if ($entity1 instanceof PHPFunction && $entity2 instanceof PHPFunction) {
            return $entity1->getName() === $entity2->getName() &&
                   $entity1->getNamespace() === $entity2->getNamespace();
        }

        if ($entity1 instanceof PHPInterface && $entity2 instanceof PHPInterface) {
            return $entity1->getName() === $entity2->getName() &&
                   $entity1->getNamespace() === $entity2->getNamespace();
        }

        if ($entity1 instanceof PHPEnum && $entity2 instanceof PHPEnum) {
            return $entity1->getName() === $entity2->getName() &&
                   $entity1->getNamespace() === $entity2->getNamespace();
        }

        if ($entity1 instanceof PHPConstant && $entity2 instanceof PHPConstant) {
            return $entity1->getName() === $entity2->getName() &&
                   $entity1->getNamespace() === $entity2->getNamespace();
        }

        return false;
    }
}
