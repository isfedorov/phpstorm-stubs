<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPClassLikeObject;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\Classes\ClassConstantsCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that constants declared in enum stubs match reflection.
 *
 * Extends ClassConstantsCheck and overrides entity lookup to use enums.
 *
 * Known problems are supported at two levels:
 *  - Entity-level: EntityType::ENUM_TYPE + enumId + 'EnumConstantsCheck' → skips entire enum.
 *  - Constant-level: EntityType::ENUM_CONSTANT + 'EnumName::CONST' + 'EnumConstantsCheck' → skips one constant.
 */
class EnumConstantsCheck extends ClassConstantsCheck
{
    protected function findEntity(ParsedDataStorageManager $storage, string $entityId): ?PHPEnum
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
        return 'EnumConstantsCheck';
    }

    protected function getConstantEntityType(): string
    {
        return EntityType::ENUM_CONSTANT->value;
    }
}
