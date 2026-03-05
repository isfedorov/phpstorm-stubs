<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsParameterDefaultValueCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that default parameter values in stub interface methods match those in reflection.
 * This check runs only against the latest PHP version.
 */
class InterfaceMethodsParameterDefaultValueCheck extends ClassMethodsParameterDefaultValueCheck
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
