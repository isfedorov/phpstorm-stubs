<?php
declare(strict_types=1);

namespace StubTests\TestData\Providers;

use ReflectionException;
use StubTests\Model\StubsContainer;
use StubTests\Parsers\PHPReflectionParser;

class ReflectionStubsSingleton
{
    private static ?StubsContainer $reflectionStubs = null;

    /**
     * @throws ReflectionException
     */
    public static function getReflectionStubs(): StubsContainer
    {
        if (self::$reflectionStubs === null) {
            self::$reflectionStubs = PHPReflectionParser::getStubs();
        }
        return self::$reflectionStubs;
    }
}
