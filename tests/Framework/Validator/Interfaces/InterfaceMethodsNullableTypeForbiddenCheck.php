<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsNullableTypeForbiddenCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that interface methods available before PHP 7.1 do not declare
 * nullable type hints — neither on their return type nor on any parameter.
 *
 * Interface methods are always public (implicitly), so all collected methods
 * are subject to the check — any implementing class must be able to satisfy the
 * method signature, which was impossible with ?T before PHP 7.1.
 */
class InterfaceMethodsNullableTypeForbiddenCheck extends ClassMethodsNullableTypeForbiddenCheck
{
    protected function findEntity(ParsedDataStorageManager $stubs, string $entityId): mixed
    {
        return $this->findInterfaceById($stubs, $entityId);
    }

    protected function collectEntityMethods(mixed $entity, string $phpVersion): array
    {
        return $this->collectVersionedInterfaceStubMethods($entity, $phpVersion);
    }

    protected function getEntityType(): string
    {
        return EntityType::INTERFACE_TYPE->value;
    }

    protected function getEntityLabel(): string
    {
        return 'Interface';
    }
}
