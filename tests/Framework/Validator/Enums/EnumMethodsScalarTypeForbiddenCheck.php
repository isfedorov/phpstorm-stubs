<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsScalarTypeForbiddenCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that enum methods available before PHP 7.0 do not declare scalar
 * parameter type hints (int, float, string, bool).
 *
 * Enums were introduced in PHP 8.1, so in practice no enum method will be
 * version-filtered into the pre-7.0 window and this check is always a no-op.
 * The class exists for completeness and to guard against future edge cases.
 */
class EnumMethodsScalarTypeForbiddenCheck extends ClassMethodsScalarTypeForbiddenCheck
{
    protected function findEntity(ParsedDataStorageManager $stubs, string $entityId): mixed
    {
        return $this->findEnumById($stubs, $entityId);
    }

    protected function collectEntityMethods(mixed $entity, string $phpVersion): array
    {
        return $this->collectVersionedEnumStubMethods($entity, $phpVersion);
    }

    protected function getEntityType(): string
    {
        return EntityType::ENUM_TYPE->value;
    }

    protected function getEntityLabel(): string
    {
        return 'Enum';
    }
}
