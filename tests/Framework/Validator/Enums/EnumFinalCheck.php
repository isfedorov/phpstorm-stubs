<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractFinalCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that the `final` modifier on an enum in stubs matches reflection.
 */
class EnumFinalCheck extends AbstractFinalCheck
{
    protected function findEntity(ParsedDataStorageManager $storage, string $entityId): mixed
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
        return 'EnumFinalCheck';
    }
}
