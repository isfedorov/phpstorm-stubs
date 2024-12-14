<?php

namespace StubTests\Unit\Reflection\Parsers;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Parsers\Entities\EntityType;
use StubTests\Sources\Parsers\Registries\EntityReflectionObjectParsersRegistry;

class ReflectionEntityParsersRegistryTest extends TestCase
{
    public function testItContainsParserForClasses()
    {
        self::assertNotNull(new EntityReflectionObjctParsersRegistry()->findParser(EntityType::A_CLASS));
    }

    public function testItContainsParserForConstants()
    {
        self::assertNotNull(new EntityReflectionObjectParsersRegistry()->findParser(EntityType::CONSTANT));
    }

    public function testItContainsParserForFunctions()
    {
        self::assertNotNull(new EntityReflectionObjectParsersRegistry()->findParser(EntityType::FUNCTION));
    }

    public function testItContainsParserForEnums()
    {
        self::assertNotNull(new EntityReflectionObjectParsersRegistry()->findParser(EntityType::ENUM));
    }
}
