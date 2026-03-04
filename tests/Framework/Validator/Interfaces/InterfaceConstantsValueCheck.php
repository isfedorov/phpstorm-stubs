<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassConstantsValueCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that the values of constants in interface stubs match reflection.
 *
 * Extends ClassConstantsValueCheck and overrides entity lookup to use interfaces.
 *
 * Known problems are supported at two levels:
 *  - Entity-level: EntityType::INTERFACE_TYPE + interfaceId + 'InterfaceConstantsValueCheck' → skips entire interface.
 *  - Constant-level: EntityType::INTERFACE_CONSTANT + 'InterfaceName::CONST' + 'InterfaceConstantsValueCheck' → skips one constant.
 */
class InterfaceConstantsValueCheck extends ClassConstantsValueCheck
{
    protected function findEntityById(ParsedDataStorageManager $storage, string $entityId): ?PHPInterface
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
        return 'InterfaceConstantsValueCheck';
    }

    protected function getConstantEntityType(): string
    {
        return EntityType::INTERFACE_CONSTANT->value;
    }
}
