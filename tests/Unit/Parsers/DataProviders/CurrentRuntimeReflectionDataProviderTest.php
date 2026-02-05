<?php

namespace StubTests\Unit\Parsers\DataProviders;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\DataProvider\CurrentRuntimeReflectionDataProvider;

class CurrentRuntimeReflectionDataProviderTest extends TestCase
{
    public function testItReturnsArrayOfFunctions()
    {
        self::assertIsArray(new CurrentRuntimeReflectionDataProvider()->getReflectionFunctions());
    }

    public function testItReturnsNotEmptyArrayOfFunctions()
    {
        self::assertNotEmpty(new CurrentRuntimeReflectionDataProvider()->getReflectionFunctions());
    }

    public function testItDoesNotReturnUsersFunctions()
    {
        $fake = <<<PHP
function my_custom_function(string \$package, string \$version, string \$message, mixed ...\$args): void {}
PHP;
        eval($fake);
        self::assertTrue(function_exists('my_custom_function'));
        $reflectionFunctions = new CurrentRuntimeReflectionDataProvider()->getReflectionFunctions();
        self::assertFalse(array_key_exists('my_custom_function', $reflectionFunctions));;
    }

    public function testItReturnsOnlyInternalFunctions()
    {
        $reflectionFunctions = new CurrentRuntimeReflectionDataProvider()->getReflectionFunctions();
        self::assertTrue(in_array('exit', $reflectionFunctions));;
    }

    public function testItReturnsAllRuntimeInternalFunctions()
    {
        $reflectionFunctions = new CurrentRuntimeReflectionDataProvider()->getReflectionFunctions();
        self::assertGreaterThan(500, sizeof($reflectionFunctions));
    }

    public function testItReturnsArrayOfClasses()
    {
        self::assertIsArray(new CurrentRuntimeReflectionDataProvider()->getReflectionClasses());
    }

    public function testItReturnsNotEmptyArrayOfClasses()
    {
        self::assertNotEmpty(new CurrentRuntimeReflectionDataProvider()->getReflectionClasses());
    }

    public function testItReturnsArrayOfActualClasses()
    {
        self::assertFalse(in_array('PropertyHookType', new CurrentRuntimeReflectionDataProvider()->getReflectionClasses()));
        self::assertFalse(in_array('Traversable', new CurrentRuntimeReflectionDataProvider()->getReflectionClasses()));
        self::assertTrue(in_array('stdClass', new CurrentRuntimeReflectionDataProvider()->getReflectionClasses()));
    }

    public function testItDoesNotReturnUsersClasses()
    {
        $fake = <<<PHP
class MyFakeClass {}
PHP;
        eval($fake);
        self::assertTrue(class_exists('MyFakeClass'));
        self::assertFalse(in_array('MyFakeClass', new CurrentRuntimeReflectionDataProvider()->getReflectionClasses()));
    }

    public function testItReturnsAllInternalClasses()
    {
        $reflectionClasses = new CurrentRuntimeReflectionDataProvider()->getReflectionClasses();
        self::assertGreaterThan(200, sizeof($reflectionClasses));
    }

    public function testItReturnsArrayOfInterfaces()
    {
        self::assertIsArray(new CurrentRuntimeReflectionDataProvider()->getReflectionInterfaces());
    }

    public function testItReturnsNotEmptyArrayOfInterfaces()
    {
        self::assertNotEmpty(new CurrentRuntimeReflectionDataProvider()->getReflectionInterfaces());
    }

    public function testItReturnsActualInterfaces()
    {
        self::assertFalse(in_array('PropertyHookType', new CurrentRuntimeReflectionDataProvider()->getReflectionInterfaces()));
        self::assertFalse(in_array('stdClass', new CurrentRuntimeReflectionDataProvider()->getReflectionInterfaces()));
        self::assertTrue(in_array('Traversable', new CurrentRuntimeReflectionDataProvider()->getReflectionInterfaces()));
    }

    public function testItDoesNotReturnUsersInterfaces()
    {
        $fake = <<<PHP
interface MyFakeInterface {}
PHP;
        eval($fake);
        self::assertTrue(interface_exists('MyFakeInterface'));
        self::assertFalse(in_array('MyFakeInterface', new CurrentRuntimeReflectionDataProvider()->getReflectionInterfaces()));
    }

    public function testItReturnsAllInternalInterfaces()
    {
        $reflectionInterfaces = new CurrentRuntimeReflectionDataProvider()->getReflectionInterfaces();
        self::assertGreaterThan(25, sizeof($reflectionInterfaces));
    }

    public function testItReturnsArrayOfEnums()
    {
        self::assertIsArray(new CurrentRuntimeReflectionDataProvider()->getReflectionEnums());
    }

    public function testItReturnsNotEmptyArrayOfEnums()
    {
        self::assertNotEmpty(new CurrentRuntimeReflectionDataProvider()->getReflectionEnums());
    }

    public function testItReturnsActualEnums()
    {
        self::assertFalse(in_array('Traversable', new CurrentRuntimeReflectionDataProvider()->getReflectionEnums()));
        self::assertFalse(in_array('stdClass', new CurrentRuntimeReflectionDataProvider()->getReflectionEnums()));
        self::assertTrue(in_array('PropertyHookType', new CurrentRuntimeReflectionDataProvider()->getReflectionEnums()));
    }

    public function testItDoesNotReturnUsersEnums()
    {
        $fake = <<<PHP
enum FakeEnum {}
PHP;
        eval($fake);
        self::assertTrue(enum_exists('FakeEnum'));
        self::assertFalse(in_array('FakeEnum', new CurrentRuntimeReflectionDataProvider()->getReflectionClasses()));
    }

    public function testItReturnsAllInternalEnums()
    {
        $reflectionEnums = new CurrentRuntimeReflectionDataProvider()->getReflectionEnums();
        self::assertGreaterThan(5, sizeof($reflectionEnums));
    }

    public function testItReturnsArrayOfConstants()
    {
        self::assertIsArray(new CurrentRuntimeReflectionDataProvider()->getReflectionConstants());
    }

    public function testItReturnsNotEmptyArrayOfConstants()
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
        self::assertTrue(defined('STUB_TESTS_CONSTANT'));
        $reflectionConstants = new CurrentRuntimeReflectionDataProvider()->getReflectionConstants();
        self::assertFalse(array_key_exists('STUB_TESTS_CONSTANT', $reflectionConstants));
    }

    public function testItContainsAllConstants()
    {
        $reflectionConstants = new CurrentRuntimeReflectionDataProvider()->getReflectionConstants();
        self::assertGreaterThan(500, sizeof($reflectionConstants));
    }
}
