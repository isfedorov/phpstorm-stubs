<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsExistCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that all methods present in reflection also exist in stubs for interfaces.
 *
 * Extends ClassMethodsExistCheck and overrides the entity-lookup template methods
 * to operate on PHPInterface objects instead of PHPClass objects.
 */
class InterfaceMethodsExistCheck extends ClassMethodsExistCheck
{
    protected function findEntityById(ParsedDataStorageManager $storage, string $entityId): mixed
    {
        return $this->findInterfaceById($storage, $entityId);
    }

    protected function collectEntityStubMethods(mixed $entity, string $phpVersion): array
    {
        return $this->collectVersionedInterfaceStubMethods($entity, $phpVersion);
    }

    protected function getEntityLabel(): string
    {
        return 'Interface';
    }

    protected function getEntityType(): string
    {
        return EntityType::INTERFACE_TYPE->value;
    }
}
