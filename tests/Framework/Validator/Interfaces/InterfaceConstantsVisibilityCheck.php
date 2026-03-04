<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassConstantsVisibilityCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that the visibility of constants in interface stubs matches reflection.
 *
 * Extends ClassConstantsVisibilityCheck and overrides entity lookup to use interfaces.
 *
 * Note: Interface constants in PHP are always public; this check is included for
 * completeness and to catch any accidental non-public visibility annotations in stubs.
 *
 * Known problems are supported at two levels:
 *  - Entity-level: EntityType::INTERFACE_TYPE + interfaceId + 'InterfaceConstantsVisibilityCheck' → skips entire interface.
 *  - Constant-level: EntityType::INTERFACE_CONSTANT + 'InterfaceName::CONST' + 'InterfaceConstantsVisibilityCheck' → skips one constant.
 */
class InterfaceConstantsVisibilityCheck extends ClassConstantsVisibilityCheck
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
        return 'InterfaceConstantsVisibilityCheck';
    }

    protected function getConstantEntityType(): string
    {
        return EntityType::INTERFACE_CONSTANT->value;
    }
}
