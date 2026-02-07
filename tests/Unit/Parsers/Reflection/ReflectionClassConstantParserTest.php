<?php

namespace StubTests\Unit\Parsers\Reflection;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionClassConstantParser;
use function PHPUnit\Framework\assertEquals;

class ReflectionClassConstantParserTest extends TestCase
{
    public function testItCanParseClassConstants()
    {
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        self::assertNotNull($parsedConstant);
    }

    public function testItReturnsCorrectInstanceOfClassConstants()
    {
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        self::assertTrue($parsedConstant instanceof PHPClassConstant);
    }

    public function testItSetsDevautNameNullForClassConstants()
    {
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        self::assertNull($parsedConstant->getName());
    }

    public function testItCanParseClassConstantName()
    {
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $classConstantMock->method('getName')->willReturn('foo');
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        self::assertEquals('foo', $parsedConstant->getName());
    }

    public function testItSetsDevautValueNullForClassConstants()
    {
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        self::assertNull($parsedConstant->getValue());
    }

    public function testItCanParseClassConstantValue()
    {
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $classConstantMock->method('getValue')->willReturn('foo');
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        self::assertEquals('foo', $parsedConstant->getValue());
    }

    public function testParsedConstantDoesnNotHaveId()
    {
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $classConstantMock->method('getName')->willReturn('foo');
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        self::assertNull($parsedConstant->getId());
    }

    public function testItSetsNullAsParentClassIfNoParentClass()
    {
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        self::assertNull($parsedConstant->parentId);
    }

    public function testItCanParseParentClassIdRootNamespace()
    {
        eval('class ParentClass { }');
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $classConstantMock->method('getDeclaringClass')->willReturn(new ReflectionClass('\ParentClass'));
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        assertEquals('\ParentClass', $parsedConstant->parentId);
    }

    public function testItCanParseParentClassIdCustomNamespace()
    {
        eval('namespace DummyNamespace; class ParentClass { }');
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $classConstantMock->method('getDeclaringClass')->willReturn(new ReflectionClass('\DummyNamespace\ParentClass'));
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        assertEquals('\DummyNamespace\ParentClass', $parsedConstant->parentId);
    }

    public function testItCanParseConstantPrivateVisibility()
    {
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $classConstantMock->method('isPrivate')->willReturn(true);
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        self::assertEquals('private', $parsedConstant->visibility);
    }

    public function testItCanParseConstantProtectedVisibility()
    {
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $classConstantMock->method('isProtected')->willReturn(true);
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        self::assertEquals('protected', $parsedConstant->visibility);
    }

    public function testItCanParseConstantPublicVisibility()
    {
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $classConstantMock->method('isPublic')->willReturn(true);
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        self::assertEquals('public', $parsedConstant->visibility);
    }

    public function testItParseVisibilityPublicIfNoVisibilityIsPresent()
    {
        $classConstantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $classConstantMock->method('isPublic')->willReturn(false);
        $classConstantMock->method('isProtected')->willReturn(false);
        $classConstantMock->method('isPrivate')->willReturn(false);
        $parsedConstant = new ReflectionClassConstantParser()->parse($classConstantMock);
        self::assertEquals('public', $parsedConstant->visibility);
    }
}
