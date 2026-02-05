<?php

namespace StubTests\Unit\Parsers\Reflection;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use StubTests\Sources\Model\Entities\PHPMethod;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionMethodParser;

class ReflectionMethodParserTest extends TestCase
{
    public function testItCanParseMethod()
    {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertNotNull($basePHPElement);
    }

    public function testItReturnsCorrectInstanceOfMethods()
    {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue($basePHPElement instanceof PHPMethod);
    }

    public function testItCanParseName()
    {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflectionMethodMock->method('getName')->willReturn('foo');
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('foo', $basePHPElement->getName());
    }

    public function testItCanParseId()
    {
        $parentClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $parentClassMock->method('getName')->willReturn('SomeNamespace\SubNamespace\SomeFooClass');
        $parentClassMock->method('getNamespaceName')->willReturn('SomeNamespace\SubNamespace');
        $parentClassMock->method('getShortName')->willReturn('SomeFooClass');
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflectionMethodMock->method('getName')->willReturn('foo');
        $reflectionMethodMock->method('getDeclaringClass')->willReturn($parentClassMock);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('\SomeNamespace\SubNamespace\SomeFooClass::foo',$basePHPElement->getId());
    }

    public function testItCanParseIdIfNoNamespaceIsPresent()
    {
        $parentClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $parentClassMock->method('getName')->willReturn('SomeFooClass');
        $parentClassMock->method('getNamespaceName')->willReturn('');
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflectionMethodMock->method('getName')->willReturn('foo');
        $reflectionMethodMock->method('getDeclaringClass')->willReturn($parentClassMock);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('\SomeFooClass::foo',$basePHPElement->getId());
    }

    public function testItCanParseVisibility() {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('isPublic')->willReturn(true);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('public', $basePHPElement->getAccess()->toString());
    }

    public function testItCanParseVisibilityIfNoVisibilityIsPresent() {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('public', $basePHPElement->getAccess()->toString());
    }

    public function testItCanParseVisibilityIfVisibilityIsPrivate() {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('isPublic')->willReturn(false);
        $reflectionMethodMock->method('isProtected')->willReturn(false);
        $reflectionMethodMock->method('isPrivate')->willReturn(true);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('private', $basePHPElement->getAccess()->toString());
    }

    public function testItCanParseVisibilityIfVisibilityIsProtected() {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('isPublic')->willReturn(false);
        $reflectionMethodMock->method('isProtected')->willReturn(true);
        $reflectionMethodMock->method('isPrivate')->willReturn(false);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('protected', $basePHPElement->getAccess()->toString());
    }

    public function testItCanParseStaticAttribute()
    {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflectionMethodMock->method('isStatic')->willReturn(true);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue($basePHPElement->isStatic());
    }

    public function testItParsesByDefaultNonStaticAttribute()
    {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isStatic());
    }

    public function testItParsesNonStaticAttribute()
    {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflectionMethodMock->method('isStatic')->willReturn(false);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isStatic());
    }

    public function testItCanParseFinalAttributeIfNoFinalAttributeIsPresent()
    {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isFinal());
    }

    public function testItCanParseFinalAttribute() {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('isFinal')->willReturn(true);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue($basePHPElement->isFinal());
    }

    public function testItCanParseNonFinalAttribute()
    {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('isFinal')->willReturn(false);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isFinal());
    }

    public function testItCanParseAbstractAttribute() {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('isAbstract')->willReturn(true);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue($basePHPElement->isAbstract());
    }

    public function testItCanParseAbstractAttributeIfNoAbstractAttributeIsPresent() {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isAbstract());
    }

    public function testItCanParseNonAbstractAttribute()
    {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('isAbstract')->willReturn(false);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isAbstract());
    }

    public function testItCanParseDeprecatedAttribute() {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('isDeprecated')->willReturn(true);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue($basePHPElement->isDeprecated());
    }

    public function testItCanParseDeprecatedAttributeIfNoDeprecatedAttributeIsPresent() {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isDeprecated());
    }

    public function testItReturnsArrayOfReturnTypes()
    {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue(is_array($basePHPElement->returnTypesFromSignature));
    }

    public function testItReturnsEmtyArrayOfReturnTypesIfNoReturnTypeIsPresent()
    {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $basePHPElement = new ReflectionObjectMethodParser()->parse($reflectionMethodMock);
        self::assertEquals([], $basePHPElement->returnTypesFromSignature);
    }

    public function testItCanParseSimpleReturnType() {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $returnTypeMock = $this->getMockBuilder(\ReflectionType::class)->disableOriginalConstructor()->getMock();
        $returnTypeMock->method('__toString')->willReturn('string');
        $reflectionMethodMock->method('hasReturnType')->willReturn(true);
        $reflectionMethodMock->method('getReturnType')->willReturn($returnTypeMock);
        $basePhpElement = new ReflectionObjectMethodParser()->parse($reflectionMethodMock);
        self::assertEquals(['string'], $basePhpElement->returnTypesFromSignature);
    }

    public function testItCanParseTentativeReturnType() {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionTypeMock = $this->getMockBuilder(\ReflectionNamedType::class)->disableOriginalConstructor()->getMock();
        $reflectionTypeMock->method('getName')->willReturn('string');
        $reflectionMethodMock->method('hasTentativeReturnType')->willReturn(true);
        $reflectionMethodMock->method('getTentativeReturnType')->willReturn($reflectionTypeMock);
        $basePHPElement = new ReflectionObjectMethodParser()->parse($reflectionMethodMock);
        self::assertTrue($basePHPElement->hasTentativeReturnType);
    }

    public function testItCanParseNoTentativeReturnType() {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionTypeMock = $this->getMockBuilder(\ReflectionNamedType::class)->disableOriginalConstructor()->getMock();

        $reflectionTypeMock->method('getName')->willReturn('string');
        $reflectionMethodMock->method('getReturnType')->willReturn($reflectionTypeMock);
        $basePHPElement = new ReflectionObjectMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->hasTentativeReturnType);
    }




    public function testItCanParseReturnTypesOfTentativeReturnType() {
        $returnTypeMock = $this->getMockBuilder(\ReflectionType::class)->disableOriginalConstructor()->getMock();
        $returnTypeMock->method('__toString')->willReturn('string');
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('hasReturnType')->willReturn(false);
        $reflectionMethodMock->method('hasTentativeReturnType')->willReturn(true);
        $reflectionMethodMock->method('getTentativeReturnType')->willReturn($returnTypeMock);
        $basePHPElement = new ReflectionObjectMethodParser()->parse($reflectionMethodMock);
        self::assertEquals(['string'], $basePHPElement->returnTypesFromSignature);
    }

}
