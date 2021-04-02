<?php
declare(strict_types=1);

namespace StubTests\TestData\Providers;

use LogicException;
use RuntimeException;
use StubTests\Model\StubsContainer;
use StubTests\Parsers\StubParser;
use UnexpectedValueException;

class PhpStormStubsSingleton
{
    private static ?StubsContainer $phpstormStubs = null;

    /**
     * @throws UnexpectedValueException
     * @throws LogicException
     * @throws RuntimeException
     */
    public static function getPhpStormStubs(): StubsContainer
    {
        if (self::$phpstormStubs === null) {
            self::$phpstormStubs = StubParser::getPhpStormStubs();
        }
        return self::$phpstormStubs;
    }
}
