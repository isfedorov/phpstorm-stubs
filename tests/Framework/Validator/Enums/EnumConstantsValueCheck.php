<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassConstantsValueCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that the values of constants in enum stubs match reflection.
 *
 * Extends ClassConstantsValueCheck and overrides entity lookup to use enums.
 *
 * Known problems are supported at two levels:
 *  - Entity-level: EntityType::ENUM_TYPE + enumId + 'EnumConstantsValueCheck' → skips entire enum.
 *  - Constant-level: EntityType::ENUM_CONSTANT + 'EnumName::CONST' + 'EnumConstantsValueCheck' → skips one constant.
 */
class EnumConstantsValueCheck extends ClassConstantsValueCheck
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
        return 'EnumConstantsValueCheck';
    }

    protected function getConstantEntityType(): string
    {
        return EntityType::ENUM_CONSTANT->value;
    }
}
