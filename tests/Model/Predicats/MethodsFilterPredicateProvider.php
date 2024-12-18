<?php

namespace StubTests\Model\Predicats;

use Closure;
use StubTests\Model\PHPMethod;
use StubTests\Parsers\ParserUtils;

class MethodsFilterPredicateProvider
{
    /**
     * @param string $methodName
     * @return Closure
     */
    public static function getDefaultSuitableMethods($methodName)
    {
        return function (PHPMethod $method) use ($methodName) {
            return $method->name === $methodName && ParserUtils::entitySuitsCurrentPhpVersion($method) && $method->getOrCreateStubSpecificProperties()->duplicateOtherElement === false;
        };
    }

    /**
     * @param string $methodName
     * @return Closure
     */
    public static function getMethodsFromReflection($methodName)
    {
        return function (PHPMethod $method) use ($methodName) {
            return $method->name == $methodName && $method->getOrCreateStubSpecificProperties()->stubObjectHash == null;
        };
    }

    /**
     * @param string $methodName
     * @return Closure
     */
    public static function getMethodsIndependingOnPHPVersion($methodName)
    {
        return function (PHPMethod $method) use ($methodName) {
            return $method->name == $methodName;
        };
    }

    /**
     * @param string $methodName
     * @return Closure
     */
    public static function getMethodsByHash($methodHash)
    {
        return function (PHPMethod $method) use ($methodHash) {
            return $method->getOrCreateStubSpecificProperties()->stubObjectHash === $methodHash;
        };
    }
}