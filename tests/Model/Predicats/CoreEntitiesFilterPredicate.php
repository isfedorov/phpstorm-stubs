<?php

namespace StubTests\Model\Predicats;

use StubTests\Model\PHPClass;
use StubTests\Model\PHPEnum;
use StubTests\Model\PHPInterface;

class CoreEntitiesFilterPredicate
{
    public static function getCoreClasses()
    {
        return function (PHPClass $class) {
            return $class->getOrCreateStubSpecificProperties()->stubBelongsToCore === true;
        };
    }

    public static function getCoreInterfaces()
    {
        return function (PHPInterface $interface) {
            return $interface->getOrCreateStubSpecificProperties()->stubBelongsToCore === true;
        };
    }

    public static function getCoreEnums()
    {
        return function (PHPEnum $enum) {
            return $enum->getOrCreateStubSpecificProperties()->stubBelongsToCore === true;
        };
    }
}