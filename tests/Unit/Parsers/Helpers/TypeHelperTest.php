<?php

namespace StubTests\Unit\Parsers\Helpers;

use PHPUnit\Framework\TestCase;
use ReflectionNamedType;
use ReflectionType;
use StubTests\Sources\Parsers\Helpers\TypeHelper;

class TypeHelperTest extends TestCase
{
    public function testItCanParseNullableReturnType()
    {
        $typeMock = $this->getMockBuilder(ReflectionType::class)->disableOriginalConstructor()->getMock();
        $typeMock->method('__toString')->willReturn('string');
        $typeMock->method('allowsNull')->willReturn(true);
        $typeAsArray = new TypeHelper()::getReflectionTypeAsArray($typeMock);
        self::assertEquals(['?string'], $typeAsArray);
    }

    public function testItCanParseNamedReturnType()
    {
        $typeMock = $this->getMockBuilder(ReflectionNamedType::class)->disableOriginalConstructor()->getMock();
        $typeMock->method('getName')->willReturn('string');
        $typeAsArray = new TypeHelper()::getReflectionTypeAsArray($typeMock);
        self::assertEquals(['string'], $typeAsArray);
    }
}
