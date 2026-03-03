<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsParameterNamesCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that parameter names in stub interface methods match those in reflection.
 * Named parameters require PHP 8.0+, so supports() checks >= 8.0.
 */
class InterfaceMethodsParameterNamesCheck extends ClassMethodsParameterNamesCheck
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
