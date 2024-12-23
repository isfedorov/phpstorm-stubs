<?php

namespace StubTests\Model\Predicats;

use StubTests\Model\PHPEnum;

class EnumsFilterPredicateProvider
{
    public static function getEnumById($enumId)
    {
        return function (PHPEnum $enum) use ($enumId) {
            return $enum->fqnBasedId === $enumId;
        };
    }

    public static function getEnumByHash(?string $classHash)
    {
        return function (PHPEnum $class) use ($classHash) {
            return $class->getOrCreateStubSpecificProperties()->stubObjectHash === $classHash;
        };
    }

    public static function getEnumIndependingOnPHPVersion($enumId)
    {
        return function (PHPEnum $enum) use ($enumId) {
            return $enum->fqnBasedId == $enumId;
        };
    }
}