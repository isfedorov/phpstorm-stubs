<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsExistCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that all methods present in reflection also exist in stubs for enums.
 *
 * Extends ClassMethodsExistCheck and overrides the entity-lookup template methods
 * to operate on PHPEnum objects instead of PHPClass objects.
 */
class EnumMethodsExistCheck extends ClassMethodsExistCheck
{
    protected function findEntityById(ParsedDataStorageManager $storage, string $entityId): mixed
    {
        return $this->findEnumById($storage, $entityId);
    }

    protected function collectEntityStubMethods(mixed $entity, string $phpVersion): array
    {
        return $this->collectVersionedEnumStubMethods($entity, $phpVersion);
    }

    protected function getEntityLabel(): string
    {
        return 'Enum';
    }

    protected function getEntityType(): string
    {
        return EntityType::ENUM_TYPE->value;
    }
}
