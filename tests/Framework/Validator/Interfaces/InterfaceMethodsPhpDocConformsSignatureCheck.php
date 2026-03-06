<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsPhpDocConformsSignatureCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that PhpDoc types in interface stubs are compatible with their signature types.
 * Interfaces have no properties, so only method return types and parameter types are checked.
 */
class InterfaceMethodsPhpDocConformsSignatureCheck extends ClassMethodsPhpDocConformsSignatureCheck
{
    protected function findStubEntity(ParsedDataStorageManager $stubs, string $entityId): mixed
    {
        return $this->findInterfaceById($stubs, $entityId);
    }

    /**
     * @return array<string, PHPMethod>
     */
    protected function getVersionedEntityMethods(mixed $entity, string $phpVersion): array
    {
        return $this->collectVersionedInterfaceStubMethods($entity, $phpVersion);
    }

    protected function getVersionedEntityProperties(mixed $entity, string $phpVersion): array
    {
        return [];
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
