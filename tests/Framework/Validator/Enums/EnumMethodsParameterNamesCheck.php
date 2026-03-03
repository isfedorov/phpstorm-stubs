<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsParameterNamesCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that parameter names in stub enum methods match those in reflection.
 * Enums are PHP 8.1+, and named parameters require PHP 8.0+, so supports() checks >= 8.0.
 */
class EnumMethodsParameterNamesCheck extends ClassMethodsParameterNamesCheck
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
