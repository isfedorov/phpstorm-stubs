<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Validator\Classes\ClassMethodsTentativeReturnTypeCheck;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that the tentative-return-type flag on enum methods in stubs matches reflection.
 * @see ClassMethodsTentativeReturnTypeCheck for full documentation.
 */
class EnumMethodsTentativeReturnTypeCheck extends ClassMethodsTentativeReturnTypeCheck
{
    protected function findEntityById(ParsedDataStorageManager $storage, string $entityId): mixed
    {
        return $this->findEnumById($storage, $entityId);
    }

    protected function collectEntityStubMethods(mixed $entity, string $phpVersion): array
    {
        return $this->collectVersionedEnumStubMethods($entity, $phpVersion);
    }

    protected function getEntityLabel(): string
    {
        return 'Enum';
    }

    protected function getEntityType(): string
    {
        return EntityType::ENUM_TYPE->value;
    }
}
