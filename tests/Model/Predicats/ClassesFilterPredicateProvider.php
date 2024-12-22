<?php

namespace StubTests\Model\Predicats;

use StubTests\Model\PHPClass;

class ClassesFilterPredicateProvider
{
    public static function getClassesByHash($classHash)
    {
        return function (PHPClass $class) use ($classHash) {
            return $class->getOrCreateStubSpecificProperties()->stubObjectHash === $classHash;
        };
    }
}