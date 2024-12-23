<?php

namespace StubTests\Model\Predicats;

use Closure;
use StubTests\Model\PHPClassConstant;
use StubTests\Model\PHPConstant;
use StubTests\Model\PHPDefineConstant;
use StubTests\Model\PHPEnumCase;
use StubTests\Parsers\ParserUtils;

class ConstantsFilterPredicateProvider
{

    /**
     * @param string $constantName
     * @return Closure
     */
    public static function getDefaultSuitableClassConstants($constantName)
    {
        return function (PHPClassConstant $constant) use ($constantName) {
            return $constant->name === $constantName && ParserUtils::entitySuitsCurrentPhpVersion($constant);
        };
    }

    public static function getDefaultSuitableConstants($constantId)
    {
        return function (PHPConstant|PHPDefineConstant $constant) use ($constantId) {
            return $constant->fqnBasedId === $constantId && ParserUtils::entitySuitsCurrentPhpVersion($constant);
        };
    }

    /**
     * @param string $constantName
     * @return Closure
     */
    public static function getClassConstantsFromReflection($constantName)
    {
        return function (PHPClassConstant $constant) use ($constantName) {
            return $constant->name == $constantName && $constant->getOrCreateStubSpecificProperties()->stubObjectHash == null;
        };
    }

    public static function getConstantById($constantId)
    {
        return function (PHPConstant $constant) use ($constantId) {
            return $constant->fqnBasedId === $constantId;
        };
    }

    public static function getConstantsFromReflection($constantId)
    {
        return function (PHPConstant $constant) use ($constantId) {
            return $constant->fqnBasedId == $constantId && $constant->getOrCreateStubSpecificProperties()->stubObjectHash == null;
        };
    }

    public static function getConstantsFromFileSuitableForPHPVersion($constantId, $sourceFilePath)
    {
        return function (PHPConstant $constant) use ($constantId, $sourceFilePath) {
            return $constant->fqnBasedId === $constantId && ParserUtils::entitySuitsCurrentPhpVersion($constant) && $constant->getOrCreateStubSpecificProperties()->sourceFilePath === $sourceFilePath;
        };
    }

    /**
     * @param string $constantName
     * @return Closure
     */
    public static function getClassConstantsIndependingOnPHPVersion($constantName)
    {
        return function (PHPClassConstant $constant) use ($constantName) {
            return $constant->name == $constantName;
        };
    }

    public static function getConstantsIndependingOnPHPVersion($constantId)
    {
        return function (PHPConstant $constant) use ($constantId) {
            return $constant->fqnBasedId == $constantId;
        };
    }

    public static function getDefaultSuitableEnumCases($caseName)
    {
        return function (PHPEnumCase $case) use ($caseName) {
            return $case->name === $caseName && ParserUtils::entitySuitsCurrentPhpVersion($case);
        };
    }

    public static function getEnumCaseFromReflection($caseName)
    {
        return function (PHPEnumCase $case) use ($caseName) {
            return $case->name == $caseName && $case->getOrCreateStubSpecificProperties()->stubObjectHash == null;
        };
    }
}