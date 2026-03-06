<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsReturnTypeForbiddenCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that interface methods available before PHP 7.0 do not declare a return type.
 *
 * Interface methods are always public (implicitly), so all collected methods
 * are subject to the check — any implementing class must be able to satisfy the
 * method signature, which was impossible with `: T` before PHP 7.0.
 */
class InterfaceMethodsReturnTypeForbiddenCheck extends ClassMethodsReturnTypeForbiddenCheck
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
