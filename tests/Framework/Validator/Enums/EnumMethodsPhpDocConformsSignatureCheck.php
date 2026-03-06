<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsPhpDocConformsSignatureCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that PhpDoc types in enum stubs are compatible with their signature types.
 * Enums have no properties, so only method return types and parameter types are checked.
 */
class EnumMethodsPhpDocConformsSignatureCheck extends ClassMethodsPhpDocConformsSignatureCheck
{
    protected function findStubEntity(ParsedDataStorageManager $stubs, string $entityId): mixed
    {
        return $this->findEnumById($stubs, $entityId);
    }

    /**
     * @return array<string, PHPMethod>
     */
    protected function getVersionedEntityMethods(mixed $entity, string $phpVersion): array
    {
        return $this->collectVersionedEnumStubMethods($entity, $phpVersion);
    }

    protected function getVersionedEntityProperties(mixed $entity, string $phpVersion): array
    {
        return [];
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
