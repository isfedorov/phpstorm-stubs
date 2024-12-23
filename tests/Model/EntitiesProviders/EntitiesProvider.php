<?php

namespace StubTests\Model\EntitiesProviders;

use StubTests\Model\StubsContainer;

class EntitiesProvider
{
    public static function getClasses(StubsContainer $container, $filterCallback = null)
    {
        if ($filterCallback) {
            return array_filter($container->getClassesManager()->getClasses(), $filterCallback);
        }
        return $container->getClassesManager()->getClasses();
    }

    public static function getClass(StubsContainer $container, $classId, $filterCallback = null)
    {
        return $container->getClassesManager()->getClass($classId, $filterCallback);
    }

    public static function getInterfaces(StubsContainer $container, $filterCallback = null)
    {
        if ($filterCallback) {
            return array_filter($container->getInterfacesManager()->getInterfaces(), $filterCallback);
        }
        return $container->getInterfacesManager()->getInterfaces();
    }

    public static function getInterface(StubsContainer $container, $interfaceId, $filterCallback = null)
    {
        return $container->getInterfacesManager()->getInterface($interfaceId, $filterCallback);
    }

    public static function getEnums(StubsContainer $container, $filterCallback = null)
    {
        if ($filterCallback) {
            return array_filter($container->getEnumsManager()->getEnums(), $filterCallback);
        }
        return $container->getEnumsManager()->getEnums();
    }

    public static function getEnum(StubsContainer $container, $enumId, $filterCallback = null)
    {
        return $container->getEnumsManager()->getEnum($enumId, $filterCallback);
    }

    public static function getConstants(StubsContainer $container, $filterCallback = null)
    {
        if ($filterCallback) {
            return array_filter($container->getConstantsManager()->getConstants(), $filterCallback);
        }
        return $container->getConstantsManager()->getConstants();
    }

    public static function getConstant(StubsContainer $container, $constantId, $filterCallback = null)
    {
        return $container->getConstantsManager()->getConstant($constantId, $filterCallback);
    }

    public static function getFunctions(StubsContainer $container, $filterCallback = null)
    {
        if ($filterCallback) {
            return array_filter($container->getFunctionsManager()->getFunctions(), $filterCallback);
        }
        return $container->getFunctionsManager()->getFunctions();
    }

    public static function getFunction(StubsContainer $container, $functionId, $filterCallback = null)
    {
        return $container->getFunctionsManager()->getFunction($functionId, $filterCallback);
    }
}