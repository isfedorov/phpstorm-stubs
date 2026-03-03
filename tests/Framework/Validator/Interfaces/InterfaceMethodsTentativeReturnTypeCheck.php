<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassMethodsTentativeReturnTypeCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that the tentative-return-type flag on interface methods in stubs matches reflection.
 * @see ClassMethodsTentativeReturnTypeCheck for full documentation.
 */
class InterfaceMethodsTentativeReturnTypeCheck extends ClassMethodsTentativeReturnTypeCheck
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
