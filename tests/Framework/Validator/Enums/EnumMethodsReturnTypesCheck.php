<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsReturnTypesCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that return types in stub enum methods match those in reflection.
 * Enums are PHP 8.1+, so return type declarations are always supported.
 */
class EnumMethodsReturnTypesCheck extends ClassMethodsReturnTypesCheck
{
    public function supports(string $phpVersion): bool
    {
        // Enums require PHP 8.1+, return types declared since PHP 7.0
        return version_compare($phpVersion, '7.0', '>=');
    }

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
