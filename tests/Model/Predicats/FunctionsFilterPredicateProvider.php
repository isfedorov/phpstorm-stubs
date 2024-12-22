<?php

namespace StubTests\Model\Predicats;

use Closure;
use StubTests\Model\PHPFunction;
use StubTests\Model\PHPMethod;
use StubTests\Parsers\ParserUtils;

class FunctionsFilterPredicateProvider
{
    /**
     * @param string $methodName
     * @return Closure
     */
    public static function getDefaultSuitableMethods($methodName)
    {
        return function (PHPMethod $method) use ($methodName) {
            return $method->name === $methodName && ParserUtils::entitySuitsCurrentPhpVersion($method);
        };
    }

    public static function getDefaultSuitableFunctions($functionId)
    {
        return function (PHPFunction $function) use ($functionId) {
            return $function->fqnBasedId === $functionId && ParserUtils::entitySuitsCurrentPhpVersion($function);
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

    public static function getFunctionsFromReflection($functionId)
    {
        return function (PHPFunction $function) use ($functionId) {
            return $function->fqnBasedId == $functionId && $function->getOrCreateStubSpecificProperties()->stubObjectHash == null;
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

    public static function getFunctionsIndependingOnPHPVersion($functionId)
    {
        return function (PHPFunction $method) use ($functionId) {
            return $method->fqnBasedId == $functionId;
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

    public static function getFunctionByHash(string $functionHash)
    {
        return function (PHPFunction $method) use ($functionHash) {
            return $method->getOrCreateStubSpecificProperties()->stubObjectHash === $functionHash;
        };
    }
}