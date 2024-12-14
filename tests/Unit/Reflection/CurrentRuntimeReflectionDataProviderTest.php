<?php

namespace StubTests\Unit\Reflection;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Parsers\CurrentRuntimeReflectionDataProvider;
use function PHPUnit\Framework\assertTrue;

class CurrentRuntimeReflectionDataProviderTest extends TestCase
{
    public function testItReturnsFunctions()
    {
        self::assertNotEmpty(new CurrentRuntimeReflectionDataProvider()->getReflectionFunctions());
    }

    public function testItDoesNotReturnUsersFunctions()
    {
        $fake = <<<PHP
function my_custom_function(string \$package, string \$version, string \$message, mixed ...\$args): void {}
PHP;
        eval($fake);
        assertTrue(function_exists('my_custom_function'));
        $reflectionFunctions = new CurrentRuntimeReflectionDataProvider()->getReflectionFunctions();
        self::assertFalse(array_key_exists('stubtests\unit\my_custom_function', $reflectionFunctions));;
    }

    public function testItReturnsOnlyInternalFunctions()
    {
        $reflectionFunctions = new CurrentRuntimeReflectionDataProvider()->getReflectionFunctions();
        self::assertTrue(in_array('exit', $reflectionFunctions));;
    }

    public function testItReturnsSomeClassLikeData()
    {
        self::assertNotEmpty(new CurrentRuntimeReflectionDataProvider()->getReflectionLikeClasses());
    }

    public function testItReturnsClasses()
    {
        self::assertTrue(in_array('stdClass', new CurrentRuntimeReflectionDataProvider()->getReflectionLikeClasses()));
    }

    public function testItDoesNotReturnUsersClasses()
    {
        $fake = <<<PHP
class MyFakeClass {}
PHP;
        eval($fake);
        assertTrue(class_exists('MyFakeClass'));
        self::assertFalse(in_array('MyFakeClass', new CurrentRuntimeReflectionDataProvider()->getReflectionLikeClasses()));
    }

    public function testItReturnsInterfaces()
    {
        self::assertTrue(in_array('Traversable', new CurrentRuntimeReflectionDataProvider()->getReflectionLikeClasses()));
    }

    public function testItDoesNotReturnUsersInterfaces()
    {
        $fake = <<<PHP
interface MyFakeInterface {}
PHP;
        eval($fake);
        assertTrue(interface_exists('MyFakeInterface'));
        self::assertFalse(in_array('MyFakeInterface', new CurrentRuntimeReflectionDataProvider()->getReflectionLikeClasses()));
    }

    public function testItReturnsEnums()
    {
        self::assertTrue(in_array('PropertyHookType', new CurrentRuntimeReflectionDataProvider()->getReflectionLikeClasses()));
    }

    public function testItDoesNotReturnUsersEnums()
    {
        $fake = <<<PHP
enum FakeEnum {}
PHP;
        eval($fake);
        assertTrue(enum_exists('FakeEnum'));
        self::assertFalse(in_array('FakeEnum', new CurrentRuntimeReflectionDataProvider()->getReflectionLikeClasses()));
    }

    public function testItReturnsConstants()
    {
        self::assertNotEmpty(new CurrentRuntimeReflectionDataProvider()->getReflectionConstants());
    }

    public function testItReturnsInternalConstants()
    {
        self::assertTrue(array_key_exists('PHP_VERSION', new CurrentRuntimeReflectionDataProvider()->getReflectionConstants()));
    }

    public function testItDoesNotReturnUsersConstants()
    {
        $fake = <<<PHP
define('STUB_TESTS_CONSTANT', 'MySomeRandomConstant');
PHP;
        eval($fake);
        assertTrue(defined('STUB_TESTS_CONSTANT'));
        $reflectionConstants = new CurrentRuntimeReflectionDataProvider()->getReflectionConstants();
        self::assertFalse(array_key_exists('STUB_TESTS_CONSTANT', $reflectionConstants));
    }
}
