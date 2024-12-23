<?php

namespace StubTests\Model\Predicats;

use StubTests\Model\PHPInterface;

class InterfaceFilterPredicateProvider
{
    public static function getInterfaceById($interfaceId)
    {
        return function (PHPInterface $interface) use ($interfaceId) {
            return $interface->fqnBasedId === $interfaceId;
        };
    }

    public static function getInterfaceByHash(?string $classHash)
    {
        return function (PHPInterface $class) use ($classHash) {
            return $class->getOrCreateStubSpecificProperties()->stubObjectHash === $classHash;
        };
    }

    public static function getInterfaceIndependingOnPHPVersion($interfaceId)
    {
        return function (PHPInterface $interface) use ($interfaceId) {
            return $interface->fqnBasedId == $interfaceId;
        };
    }
}