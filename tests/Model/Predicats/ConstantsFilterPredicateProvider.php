<?php

namespace StubTests\Model\Predicats;

use Closure;
use StubTests\Model\PHPClassConstant;
use StubTests\Parsers\ParserUtils;

class ConstantsFilterPredicateProvider
{

    /**
     * @param string $constantName
     * @return Closure
     */
    public static function getDefaultSuitableConstants($constantName)
    {
        return function (PHPClassConstant $constant) use ($constantName) {
            return $constant->name === $constantName && ParserUtils::entitySuitsCurrentPhpVersion($constant) && $constant->duplicateOtherElement === false;
        };
    }

    /**
     * @param string $constantName
     * @return Closure
     */
    public static function getConstantsFromReflection($constantName)
    {
        return function (PHPClassConstant $constant) use ($constantName) {
            return $constant->name == $constantName && $constant->stubObjectHash == null;
        };
    }

    /**
     * @param string $constantName
     * @return Closure
     */
    public static function getConstantsIndependingOnPHPVersion($constantName)
    {
        return function (PHPClassConstant $constant) use ($constantName) {
            return $constant->name == $constantName;
        };
    }
}