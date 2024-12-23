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

    public static function getClass(StubsContainer $container, $filterCallback)
    {
        return $container->getClassesManager()->getClassNew($filterCallback);
    }

    public static function getInterfaces(StubsContainer $container, $filterCallback = null)
    {
        if ($filterCallback) {
            return array_filter($container->getInterfacesManager()->getInterfaces(), $filterCallback);
        }
        return $container->getInterfacesManager()->getInterfaces();
    }

    public static function getInterface(StubsContainer $container, $filterCallback)
    {
        return $container->getInterfacesManager()->getInterface($filterCallback);
    }

    public static function getEnums(StubsContainer $container, $filterCallback = null)
    {
        if ($filterCallback) {
            return array_filter($container->getEnumsManager()->getEnums(), $filterCallback);
        }
        return $container->getEnumsManager()->getEnums();
    }

    public static function getEnum(StubsContainer $container, $filterCallback)
    {
        return $container->getEnumsManager()->getEnum($filterCallback);
    }

    public static function getConstants(StubsContainer $container, $filterCallback = null)
    {
        if ($filterCallback) {
            return array_filter($container->getConstantsManager()->getConstants(), $filterCallback);
        }
        return $container->getConstantsManager()->getConstants();
    }

    public static function getConstant(StubsContainer $container, $filterCallback)
    {
        return $container->getConstantsManager()->getConstant($filterCallback);
    }

    public static function getFunctions(StubsContainer $container, $filterCallback = null)
    {
        if ($filterCallback) {
            return array_filter($container->getFunctionsManager()->getFunctions(), $filterCallback);
        }
        return $container->getFunctionsManager()->getFunctions();
    }

    public static function getFunction(StubsContainer $container, $filterCallback)
    {
        return $container->getFunctionsManager()->getFunction($filterCallback);
    }
}