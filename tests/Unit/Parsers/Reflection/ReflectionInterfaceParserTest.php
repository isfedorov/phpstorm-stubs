<?php

namespace StubTests\Unit\Parsers\Reflection;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use StubTests\Sources\Model\Entities\PHPInterface;
use StubTests\Sources\Model\Entities\PHPMethod;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionInterfaceParser;

class ReflectionInterfaceParserTest extends TestCase
{
    public function testItCanParseInternalInterface()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(true);
        $stubReflectionClass->method('isInterface')->willReturn(true);
        self::assertTrue(new ReflectionInterfaceParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseUserInterface()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(false);
        $stubReflectionClass->method('isInterface')->willReturn(true);
        self::assertFalse(new ReflectionInterfaceParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseInternalNonInterface()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(true);
        $stubReflectionClass->method('isInterface')->willReturn(false);
        self::assertFalse(new ReflectionInterfaceParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseUsersNonInterface()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(false);
        $stubReflectionClass->method('isInterface')->willReturn(false);
        self::assertFalse(new ReflectionInterfaceParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseInternalEnums()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(true);
        $stubReflectionClass->method('isEnum')->willReturn(true);
        self::assertFalse(new ReflectionInterfaceParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseUsersEnums()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(false);
        $stubReflectionClass->method('isEnum')->willReturn(true);
        self::assertFalse(new ReflectionInterfaceParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItReturnsCorrectInstance()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionMock);
        self::assertTrue($basePHPElement instanceof PHPInterface);
    }

    public function testItCanParseName()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getShortName')->willReturn('Foo');
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionMock);
        self::assertEquals('Foo', $basePHPElement->getName());
    }

    public function testItCanParseNamespace()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getNamespaceName')->willReturn('MyNameSpace\SubNameSpace');
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionMock);
        self::assertEquals('\MyNameSpace\SubNameSpace', $basePHPElement->getNamespace());
    }

    public function testItCanParseRootNamespace()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getNamespaceName')->willReturn('');
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionMock);
        self::assertEquals('\\', $basePHPElement->getNamespace());
    }

    public function testItCanParseId()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getShortName')->willReturn('SomeFooClass');
        $reflectionMock->method('getName')->willReturn('SomeFooClass');
        $reflectionMock->method('getNamespaceName')->willReturn('SomeNamespace\SubNamespace');
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionMock);
        self::assertEquals('\SomeNamespace\SubNamespace\SomeFooClass',$basePHPElement->getId());
    }

    public function testItCanParseIdWithRootNamespace()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getShortName')->willReturn('SomeFooClass');
        $reflectionMock->method('getName')->willReturn('SomeFooClass');
        $reflectionMock->method('getNamespaceName')->willReturn('');
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionMock);
        self::assertEquals('\SomeFooClass',$basePHPElement->getId());
    }

    public function testItCanParseMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock = $this->getMockBuilder(\ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('getName')->willReturn('foo');
        $reflectionClassMock->method('getMethods')->willReturn([$reflectionMethodMock]);
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getMethods());
        self::assertNotEmpty($basePHPElement->getMethods());
    }

    public function testItReturnsCorrectInstanceOfParsedMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock = $this->getMockBuilder(\ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('getName')->willReturn('foo');
        $reflectionClassMock->method('getMethods')->willReturn([$reflectionMethodMock]);
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionClassMock);
        self::assertInstanceOf(PHPMethod::class, $basePHPElement->getMethods()[0]);
    }

    public function testItReturnsEmptyArrayIfMethodsCanNotBeRead()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getMethods());
        self::assertIsArray($basePHPElement->getMethods());
        self::assertEmpty($basePHPElement->getMethods());
    }

    public function testItReturnsEmptyArrayIfNoMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getMethods')->willReturn([]);
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getMethods());
        self::assertIsArray($basePHPElement->getMethods());
        self::assertEmpty($basePHPElement->getMethods());
    }

    public function testItReturnsActualParsedMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock = $this->getMockBuilder(\ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('getName')->willReturn('foo');
        $reflectionClassMock->method('getMethods')->willReturn([$reflectionMethodMock]);
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionClassMock);
        self::assertEquals('foo', $basePHPElement->getMethods()[0]->getName());
    }

    public function testItReturnsAllActuallyParsedMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock1 = $this->getMockBuilder(\ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock2 = $this->getMockBuilder(\ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock3 = $this->getMockBuilder(\ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock1->method('getName')->willReturn('foo1');
        $reflectionMethodMock2->method('getName')->willReturn('foo2');
        $reflectionMethodMock3->method('getName')->willReturn('foo3');
        $reflectionClassMock->method('getMethods')->willReturn([$reflectionMethodMock1, $reflectionMethodMock2, $reflectionMethodMock3]);
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionClassMock);
        self::assertEquals('foo1', $basePHPElement->getMethods()[0]->getName());
        self::assertEquals('foo2', $basePHPElement->getMethods()[1]->getName());
        self::assertEquals('foo3', $basePHPElement->getMethods()[2]->getName());
    }

    public function testItCanParseConstants()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('hasMethod')->with('getReflectionConstants')->willReturn(true);
        $reflectionClassConstantsMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $reflectionClassConstantsMock->method('getName')->willReturn('FOO');
        $reflectionClassConstantsMock->method('getValue')->willReturn('BAR');
        $reflectionClassMock->method('getReflectionConstants')->willReturn([$reflectionClassConstantsMock]);
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getConstants());
        self::assertNotEmpty($basePHPElement->getConstants());
    }

    public function testItCanParseInterfaceMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $methodMock = $this->getMockBuilder(\ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $methodMock->method('getName')->willReturn('foo');
        $methodMock->method('getDeclaringClass')->willReturn($reflectionClassMock);
        $reflectionClassMock->method('getMethods')->willReturn([$methodMock]);
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionClassMock);
        self::assertEquals(1, sizeof($basePHPElement->getMethods()));
    }

    public function testItDoesNotReturnNullIfNoConstants()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getConstants')->willReturn([]);
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getConstants());
    }

    public function testItReturnsEmptyArrayIfNoConstants()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getConstants')->willReturn([]);
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionClassMock);
        self::assertIsArray($basePHPElement->getConstants());
        self::assertEmpty($basePHPElement->getConstants());
    }

    public function testItCanParseInterfaceConstants()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $constantMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $constantMock->method('getName')->willReturn('FOO');
        $constantMock->method('getValue')->willReturn('BAR');
        $constantMock->method('isEnumCase')->willReturn(false);
        $reflectionClassMock->method('hasMethod')->with('getReflectionConstants')->willReturn(true);
        $reflectionClassMock->method('getReflectionConstants')->willReturn([$constantMock]);
        $basePHPElement = new ReflectionInterfaceParser()->parse($reflectionClassMock);
        self::assertEquals(1, sizeof($basePHPElement->getConstants()));
    }
}
