<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassConstantsVisibilityCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that the visibility of constants in enum stubs matches reflection.
 *
 * Extends ClassConstantsVisibilityCheck and overrides entity lookup to use enums.
 *
 * Known problems are supported at two levels:
 *  - Entity-level: EntityType::ENUM_TYPE + enumId + 'EnumConstantsVisibilityCheck' → skips entire enum.
 *  - Constant-level: EntityType::ENUM_CONSTANT + 'EnumName::CONST' + 'EnumConstantsVisibilityCheck' → skips one constant.
 */
class EnumConstantsVisibilityCheck extends ClassConstantsVisibilityCheck
{
    protected function findEntityById(ParsedDataStorageManager $storage, string $entityId): ?PHPEnum
    {
        return $this->findEnumById($storage, $entityId);
    }

    protected function getEntityLabel(): string
    {
        return 'Enum';
    }

    protected function getEntityType(): string
    {
        return EntityType::ENUM_TYPE->value;
    }

    protected function getCheckName(): string
    {
        return 'EnumConstantsVisibilityCheck';
    }

    protected function getConstantEntityType(): string
    {
        return EntityType::ENUM_CONSTANT->value;
    }
}
