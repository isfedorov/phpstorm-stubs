<?php
declare(strict_types=1);

namespace StubTests;

use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use StubTests\TestData\Providers\ReflectionStubsSingleton;
use UnexpectedValueException;

abstract class BaseStubsTest extends TestCase
{
    /**
     * @throws UnexpectedValueException
     * @throws LogicException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        PhpStormStubsSingleton::getPhpStormStubs();
        ReflectionStubsSingleton::getReflectionStubs();
    }
}
