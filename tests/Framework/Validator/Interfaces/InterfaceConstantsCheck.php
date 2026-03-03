<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\Entities\Model\PHPClassLikeObject;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassConstantsCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that constants declared in interface stubs match reflection.
 *
 * Extends ClassConstantsCheck and overrides entity lookup to use interfaces.
 *
 * Known problems are supported at two levels:
 *  - Entity-level: EntityType::INTERFACE_TYPE + interfaceId + 'InterfaceConstantsCheck' → skips entire interface.
 *  - Constant-level: EntityType::INTERFACE_CONSTANT + 'InterfaceName::CONST' + 'InterfaceConstantsCheck' → skips one constant.
 */
class InterfaceConstantsCheck extends ClassConstantsCheck
{
    protected function findEntity(ParsedDataStorageManager $storage, string $entityId): ?PHPInterface
    {
        return $this->findInterfaceById($storage, $entityId);
    }

    protected function getEntityLabel(): string
    {
        return 'Interface';
    }

    protected function getEntityType(): string
    {
        return EntityType::INTERFACE_TYPE->value;
    }

    protected function getCheckName(): string
    {
        return 'InterfaceConstantsCheck';
    }

    protected function getConstantEntityType(): string
    {
        return EntityType::INTERFACE_CONSTANT->value;
    }
}
