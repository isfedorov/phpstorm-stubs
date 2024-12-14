<?php

namespace StubTests\Unit\Reflection\Parsers;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionClassLikeParsersRegistry;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionClassObjectFactory;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionObjectClassParser;

class ReflectionParserRegistryTest extends TestCase
{
    public function testItContainsParserForClasses()
    {
        $reflectionClassParserStub = $this->createMock(ReflectionObjectClassParser::class);
        $reflectionClassParserStub->method('canParseReflectionClass')->with(new \ReflectionClass('stdClass'))->willReturn(true);
        self::assertNotNull(new ReflectionClassLikeParsersRegistry($reflectionClassParserStub)->findParser(new \ReflectionClass('stdClass')));
    }

    public function testItContainsParserForInterfaces()
    {
        $reflectionClassParserStub = $this->createMock(ReflectionObjectClassParser::class);
        $reflectionClassParserStub->method('canParseReflectionClass')->with(new \ReflectionClass('Traversable'))->willReturn(true);
        self::assertNotNull(new ReflectionClassLikeParsersRegistry($reflectionClassParserStub)->findParser(new \ReflectionClass('Traversable')));
    }

    public function testItContainsParserForEnums()
    {
        $reflectionClassMock = $this->getMockBuilder(\ReflectionClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'isEnum'])
            ->getMock();
        $reflectionClassMock->method('getName')->willReturn('QosClass');
        $reflectionClassMock->method('isEnum')->willReturn(true);
        $reflectionClassObjectFactory = $this->getMockBuilder(ReflectionClassObjectFactory::class)
            ->onlyMethods(['createReflectionClass'])
            ->getMock();
        $reflectionClassObjectFactory->method('createReflectionClass')->willReturn($reflectionClassMock);
        $reflectionClassParserStub = $this->createMock(ReflectionObjectClassParser::class);
        $reflectionClassParserStub->method('canParseReflectionClass')->with($reflectionClassMock)->willReturn(true);
        self::assertNotNull(new ReflectionClassLikeParsersRegistry($reflectionClassParserStub)->findParser($reflectionClassObjectFactory->createReflectionClass('QosClass')));
    }

    public function testItReturnsNullIfNoParserFoundForClass()
    {
        $reflectionClassParserStub = $this->createMock(ReflectionObjectClassParser::class);
        $reflectionClassParserStub->method('canParseReflectionClass')->with(new \ReflectionClass('Traversable'))->willReturn(false);
        self::assertNull(new ReflectionClassLikeParsersRegistry(new ReflectionObjectClassParser())->findParser(new \ReflectionClass('Traversable')));
    }
}