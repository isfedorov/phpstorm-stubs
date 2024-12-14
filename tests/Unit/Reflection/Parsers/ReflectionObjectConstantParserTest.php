<?php

namespace StubTests\Unit\Reflection\Parsers;

use PHPUnit\Framework\TestCase;
use ReflectionConstant;
use StubTests\Sources\Model\Entities\PHPConstant;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionObjectConstantParser;

class ReflectionObjectConstantParserTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        eval('const DUMMY_CONSTANT = "TestValue";');
        eval('namespace TestNamespace; const DUMMY_CONSTANT = "TestValue";');
    }

    public function testItCanParseConstants()
    {
        self::assertNotNull(new ReflectionObjectConstantParser()->parse(new ReflectionConstant('DUMMY_CONSTANT')));
    }

    public function testItReturnsCorrectInstanceOfConstants()
    {
        $parsedObject = new ReflectionObjectConstantParser()->parse(new ReflectionConstant('DUMMY_CONSTANT'));
        self::assertTrue($parsedObject instanceof PHPConstant);
    }

    public function testItCanParseConstantNameForModernConstant()
    {
        $parsedObject = new ReflectionObjectConstantParser()->parse(new ReflectionConstant('DUMMY_CONSTANT'));
        self::assertEquals("DUMMY_CONSTANT", $parsedObject->name);
    }

    public function testItCanParseConstantValueForModernConstant()
    {
        $parsedObject = new ReflectionObjectConstantParser()->parse(new ReflectionConstant('DUMMY_CONSTANT'));
        self::assertEquals("TestValue", $parsedObject->value);
    }

    public function testItCanParseConstantIdForModernConstant()
    {
        $parsedObject = new ReflectionObjectConstantParser()->parse(new ReflectionConstant('DUMMY_CONSTANT'));
        self::assertEquals("\DUMMY_CONSTANT", $parsedObject->id);
    }

    public function testItCanParseConstantIdForModernConstantUnderNamespace()
    {
        $parsedObject = new ReflectionObjectConstantParser()->parse(new ReflectionConstant('\TestNamespace\DUMMY_CONSTANT'));
        self::assertEquals("\TestNamespace\DUMMY_CONSTANT", $parsedObject->id);
    }
}
