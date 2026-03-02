<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsParameterTypesCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that parameter types of interface methods in stubs match reflection.
 */
class InterfaceMethodsParameterTypesCheck extends ClassMethodsParameterTypesCheck
{
    public function supports(string $phpVersion): bool
    {
        // Scalar type hints were introduced in PHP 7.0
        return version_compare($phpVersion, '7.0', '>=');
    }

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
