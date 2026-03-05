<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsParameterDefaultValueCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that default parameter values in stub enum methods match those in reflection.
 * Enums are PHP 8.1+; this check runs only against the latest PHP version.
 */
class EnumMethodsParameterDefaultValueCheck extends ClassMethodsParameterDefaultValueCheck
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
