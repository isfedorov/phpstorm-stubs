<?php

namespace StubTests\Unit\Parsers\Reflection;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionMethodParser;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionClass;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionMethod;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionType;

class ReflectionMethodParserTest extends TestCase
{
    public function testItCanParseMethod()
    {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertNotNull($basePHPElement);
    }

    public function testItReturnsCorrectInstanceOfMethods()
    {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue($basePHPElement instanceof PHPMethod);
    }

    public function testItCanParseName()
    {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName'])
            ->getMock();
        $reflectionMethodMock->method('getName')->willReturn('foo');
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('foo', $basePHPElement->getName());
    }

    public function testItCanParseId()
    {
        $parentClassMock = $this->getMockBuilder(AdaptedReflectionClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getNamespaceName', 'getName', 'getShortName'])
            ->getMock();
        $parentClassMock->method('getName')->willReturn('SomeNamespace\SubNamespace\SomeFooClass');
        $parentClassMock->method('getNamespaceName')->willReturn('SomeNamespace\SubNamespace');
        $parentClassMock->method('getShortName')->willReturn('SomeFooClass');
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'getDeclaringClass'])
            ->getMock();
        $reflectionMethodMock->method('getName')->willReturn('foo');
        $reflectionMethodMock->method('getDeclaringClass')->willReturn($parentClassMock);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('\SomeNamespace\SubNamespace\SomeFooClass::foo',$basePHPElement->getId());
    }

    public function testItCanParseIdIfNoNamespaceIsPresent()
    {
        $parentClassMock = $this->getMockBuilder(AdaptedReflectionClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'getNamespaceName'])
            ->getMock();
        $parentClassMock->method('getName')->willReturn('SomeFooClass');
        $parentClassMock->method('getNamespaceName')->willReturn('');
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'getDeclaringClass'])
            ->getMock();
        $reflectionMethodMock->method('getName')->willReturn('foo');
        $reflectionMethodMock->method('getDeclaringClass')->willReturn($parentClassMock);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('\SomeFooClass::foo',$basePHPElement->getId());
    }

    public function testItCanParseVisibility() {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isPublic'])
            ->getMock();
        $reflectionMethodMock->method('isPublic')->willReturn(true);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('public', $basePHPElement->getAccess()->toString());
    }

    public function testItCanParseVisibilityIfNoVisibilityIsPresent() {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isPublic'])
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('public', $basePHPElement->getAccess()->toString());
    }

    public function testItCanParseVisibilityIfVisibilityIsPrivate() {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isPublic', 'isProtected', 'isPrivate'])
            ->getMock();
        $reflectionMethodMock->method('isPublic')->willReturn(false);
        $reflectionMethodMock->method('isProtected')->willReturn(false);
        $reflectionMethodMock->method('isPrivate')->willReturn(true);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('private', $basePHPElement->getAccess()->toString());
    }

    public function testItCanParseVisibilityIfVisibilityIsProtected() {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isPublic', 'isProtected', 'isPrivate'])
            ->getMock();
        $reflectionMethodMock->method('isPublic')->willReturn(false);
        $reflectionMethodMock->method('isProtected')->willReturn(true);
        $reflectionMethodMock->method('isPrivate')->willReturn(false);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals('protected', $basePHPElement->getAccess()->toString());
    }

    public function testItCanParseStaticAttribute()
    {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isStatic'])
            ->getMock();
        $reflectionMethodMock->method('isStatic')->willReturn(true);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue($basePHPElement->isStatic());
    }

    public function testItParsesByDefaultNonStaticAttribute()
    {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isStatic());
    }

    public function testItParsesNonStaticAttribute()
    {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isStatic'])
            ->getMock();
        $reflectionMethodMock->method('isStatic')->willReturn(false);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isStatic());
    }

    public function testItCanParseFinalAttributeIfNoFinalAttributeIsPresent()
    {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isFinal());
    }

    public function testItCanParseFinalAttribute() {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isFinal'])
            ->getMock();
        $reflectionMethodMock->method('isFinal')->willReturn(true);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue($basePHPElement->isFinal());
    }

    public function testItCanParseNonFinalAttribute()
    {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isFinal'])
            ->getMock();
        $reflectionMethodMock->method('isFinal')->willReturn(false);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isFinal());
    }

    public function testItCanParseAbstractAttribute() {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isAbstract'])
            ->getMock();
        $reflectionMethodMock->method('isAbstract')->willReturn(true);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue($basePHPElement->isAbstract());
    }

    public function testItCanParseAbstractAttributeIfNoAbstractAttributeIsPresent() {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isAbstract());
    }

    public function testItCanParseNonAbstractAttribute()
    {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isAbstract'])
            ->getMock();
        $reflectionMethodMock->method('isAbstract')->willReturn(false);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isAbstract());
    }

    public function testItCanParseDeprecatedAttribute() {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDeprecated'])
            ->getMock();
        $reflectionMethodMock->method('isDeprecated')->willReturn(true);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue($basePHPElement->isDeprecated());
    }

    public function testItCanParseDeprecatedAttributeIfNoDeprecatedAttributeIsPresent() {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->isDeprecated());
    }

    public function testItReturnsArrayOfReturnTypes()
    {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue(is_array($basePHPElement->getReturnTypeFromSignature()));
    }

    public function testItReturnsEmtyArrayOfReturnTypesIfNoReturnTypeIsPresent()
    {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals([], $basePHPElement->getReturnTypeFromSignature());
    }

    public function testItCanParseSimpleReturnType() {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $returnTypeMock = $this->getMockBuilder(AdaptedReflectionType::class)->disableOriginalConstructor()->getMock();
        $returnTypeMock->method('__toString')->willReturn('string');
        $reflectionMethodMock->method('hasReturnType')->willReturn(true);
        $reflectionMethodMock->method('getReturnType')->willReturn($returnTypeMock);
        $basePhpElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals(['string'], $basePhpElement->getReturnTypeFromSignature());
    }

    public function testItCanParseTentativeReturnType() {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionTypeMock = $this->getMockBuilder(AdaptedReflectionType::class)->disableOriginalConstructor()->getMock();
        $reflectionTypeMock->method('getName')->willReturn('string');
        $reflectionMethodMock->method('hasTentativeReturnType')->willReturn(true);
        $reflectionMethodMock->method('getTentativeReturnType')->willReturn($reflectionTypeMock);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertTrue($basePHPElement->hasTentativeReturnType());
    }

    public function testItCanParseNoTentativeReturnType() {
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionTypeMock = $this->getMockBuilder(AdaptedReflectionType::class)->disableOriginalConstructor()->getMock();

        $reflectionTypeMock->method('getName')->willReturn('string');
        $reflectionMethodMock->method('getReturnType')->willReturn($reflectionTypeMock);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertFalse($basePHPElement->hasTentativeReturnType());
    }

    public function testItCanParseReturnTypesOfTentativeReturnType() {
        $returnTypeMock = $this->getMockBuilder(AdaptedReflectionType::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__toString'])
            ->getMock();
        $returnTypeMock->method('__toString')->willReturn('string');
        $reflectionMethodMock = $this->getMockBuilder(AdaptedReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('hasReturnType')->willReturn(false);
        $reflectionMethodMock->method('hasTentativeReturnType')->willReturn(true);
        $reflectionMethodMock->method('getTentativeReturnType')->willReturn($returnTypeMock);
        $basePHPElement = new ReflectionMethodParser()->parse($reflectionMethodMock);
        self::assertEquals(['string'], $basePHPElement->getReturnTypeFromSignature());
    }

}
