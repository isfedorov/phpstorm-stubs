<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsParameterTypesCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that parameter types in stub enum methods match those in reflection.
 * Enums are PHP 8.1+, so parameter type declarations are always supported.
 */
class EnumMethodsParameterTypesCheck extends ClassMethodsParameterTypesCheck
{
    public function supports(string $phpVersion): bool
    {
        // Enums require PHP 8.1+, parameter types declared since PHP 7.0
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
