<?php

namespace StubTests\Unit\Parsers\Reflection;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Parsers\Entities\EntityType;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionClassParser;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionEnumParser;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionFunctionParser;
use StubTests\Sources\Parsers\Registries\EntityReflectionObjectParsersRegistry;

class ReflectionEntityParsersRegistryTest extends TestCase
{
    public function testItReturnsNullForUnknownType()
    {
        self::assertNull(new EntityReflectionObjectParsersRegistry()->findParser(EntityType::UNKNOWN));
    }

    public function testItContainsParserForClasses()
    {
        self::assertNotNull(new EntityReflectionObjectParsersRegistry()->findParser(EntityType::A_CLASS));
    }

    public function testItContainsExactClassParserForClasses()
    {
        self::assertInstanceOf(ReflectionClassParser::class, new EntityReflectionObjectParsersRegistry()->findParser(EntityType::A_CLASS));
    }

    public function testItContainsParserForConstants()
    {
        self::assertNotNull(new EntityReflectionObjectParsersRegistry()->findParser(EntityType::CONSTANT));
    }

    public function testItContainsExactConstantParserForConstants()
    {
        self::assertInstanceOf(\StubTests\Sources\Parsers\Entities\Reflection\ReflectionModernConstantParser::class, new EntityReflectionObjectParsersRegistry()->findParser(EntityType::CONSTANT));
    }

    public function testItContainsParserForFunctions()
    {
        self::assertNotNull(new EntityReflectionObjectParsersRegistry()->findParser(EntityType::FUNCTION));
    }

    public function testItContainsExactFunctionParserForFunctions()
    {
        self::assertInstanceOf(ReflectionFunctionParser::class, new EntityReflectionObjectParsersRegistry()->findParser(EntityType::FUNCTION));
    }

    public function testItContainsParserForEnums()
    {
        self::assertNotNull(new EntityReflectionObjectParsersRegistry()->findParser(EntityType::ENUM));
    }

    public function testItContainsExactEnumParserForEnums()
    {
        self::assertInstanceOf(ReflectionEnumParser::class, new EntityReflectionObjectParsersRegistry()->findParser(EntityType::ENUM));
    }
}
