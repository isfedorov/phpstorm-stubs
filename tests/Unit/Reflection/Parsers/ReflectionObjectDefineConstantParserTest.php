<?php

namespace StubTests\Unit\Reflection\Parsers;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Model\Entities\PHPConstant;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionObjectDefineConstantParser;

class ReflectionObjectDefineConstantParserTest extends TestCase
{
    public function testItCanParseDefineConstant()
    {
        self::assertNotNull(new ReflectionObjectDefineConstantParser()->parse(['MY_DUMMY_CONSTANT', '7.4.0']));
    }

    public function testItCanReturnsCorrectInstanceOfConstant()
    {
        $parsedConstant = new ReflectionObjectDefineConstantParser()->parse(['MY_DUMMY_CONSTANT', '7.4.0']);
        self::assertTrue($parsedConstant instanceof PHPConstant);
    }

    public function testItCanParseStringConstantNameForDefinedConstant()
    {
        $parsedObject = new ReflectionObjectDefineConstantParser()->parse(['MY_DUMMY_CONSTANT', '7.4.0']);
        self::assertEquals("MY_DUMMY_CONSTANT", $parsedObject->name);
    }

    public function testItCanParseIntConstantNameForDefinedConstant()
    {
        $parsedObject = new ReflectionObjectDefineConstantParser()->parse([1, '7.4.0']);
        self::assertEquals("1", $parsedObject->name);
    }

    public function testItCanParseStringConstantValueForDefinedConstant()
    {
        $parsedObject = new ReflectionObjectDefineConstantParser()->parse(['MY_DUMMY_CONSTANT', '7.4.0']);
        self::assertEquals("7.4.0", $parsedObject->value);
    }

    public function testItCanParseIntConstantValueForDefinedConstant()
    {
        $parsedObject = new ReflectionObjectDefineConstantParser()->parse(['MY_DUMMY_CONSTANT', 1]);
        self::assertEquals("1", $parsedObject->value);
    }

    public function testItCanParseFloatConstantValueForDefinedConstant()
    {
        $parsedObject = new ReflectionObjectDefineConstantParser()->parse(['MY_DUMMY_CONSTANT', 7.4]);
        self::assertEquals("7.4", $parsedObject->value);
    }

    public function testItCanParseResourceConstantValueForDefinedConstant()
    {
        $resource = fopen('php://memory', 'r+');
        $parsedObject = new ReflectionObjectDefineConstantParser()->parse(['MY_DUMMY_CONSTANT', $resource]);
        self::assertEquals("PHPSTORM_RESOURCE", $parsedObject->value);
        fclose($resource);
    }

    public function testItCanParseNullConstantValueForDefinedConstant()
    {
        $parsedObject = new ReflectionObjectDefineConstantParser()->parse(['MY_DUMMY_CONSTANT', null]);
        self::assertNull($parsedObject->value);
    }

    public function testItCanParseConstantIdForDefinedConstant()
    {
        $parsedObject = new ReflectionObjectDefineConstantParser()->parse(['MY_DUMMY_CONSTANT', '7.4.0']);
        self::assertEquals("\MY_DUMMY_CONSTANT", $parsedObject->id);
    }
}
