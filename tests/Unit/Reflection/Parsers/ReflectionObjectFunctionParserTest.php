<?php

namespace StubTests\Unit\Reflection\Parsers;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use StubTests\Sources\Model\Entities\PHPFunction;
use StubTests\Sources\Model\EntitiesManagers\Additional\AdditionalManagerType;
use StubTests\Sources\Parsers\Entities\EntityType;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionObjectFunctionParser;

class ReflectionObjectFunctionParserTest extends TestCase
{
    public function testItApplicableToFunction()
    {
        self::assertTrue(new ReflectionObjectFunctionParser()->applicableTo(EntityType::FUNCTION));
    }

    public function testItNotApplicableToMethod()
    {
        self::assertFalse(new ReflectionObjectFunctionParser()->applicableTo(EntityType::METHOD));
    }

    public function testItNotApplicableToClass()
    {
        self::assertFalse(new ReflectionObjectFunctionParser()->applicableTo(EntityType::A_CLASS));
    }

    public function testItReturnsCorrectInstanceOfFunction()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertTrue($basePHPElement instanceof PHPFunction);
    }

    public function testItContainsParameterManager()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertNotNull($basePHPElement->getAdditionalManager(AdditionalManagerType::ParametersManager));
    }

    public function testItDoesNotContainPhpDocManager()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertNull($basePHPElement->getAdditionalManager(AdditionalManagerType::PhpDocManager));
    }

    public function testItDoesNotContainStubsSpecificManagers()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertNull($basePHPElement->getAdditionalManager(AdditionalManagerType::StubsSpecificPropertiesManager));
    }

    public function testItCanParseName()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $functionMock->method('getName')->willReturn('foo');
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertEquals('foo', $basePHPElement->name);
    }

    public function testItCanParseNamespace()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $functionMock->method('getNamespaceName')->willReturn('MyNameSpace\SubNameSpace');
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertEquals('\MyNameSpace\SubNameSpace', $basePHPElement->namespace);
    }

    public function testItCanParseRootNamespace()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $functionMock->method('getNamespaceName')->willReturn('');
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertEquals('', $basePHPElement->namespace);
    }

    public function testItCanParseId()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $functionMock->method('getName')->willReturn('foo');
        $functionMock->method('getNamespaceName')->willReturn('SomeNamespace\SubNamespace');
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertEquals('\SomeNamespace\SubNamespace\foo', $basePHPElement->id);
    }

    public function testItCanParseIdWithRootNamespace()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $functionMock->method('getName')->willReturn('foo');
        $functionMock->method('getNamespaceName')->willReturn('');
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertEquals('\foo', $basePHPElement->id);
    }

    public function testItCanParseReturnType()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $returnTypeMock = $this->getMockBuilder(\ReflectionNamedType::class)->disableOriginalConstructor()->getMock();
        $returnTypeMock->method('getName')->willReturn('string');
        $functionMock->method('getReturnType')->willReturn($returnTypeMock);
        $functionMock->method('hasReturnType')->willReturn(true);
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertContains('string', $basePHPElement->returnTypesFromSignature);
    }

    public function testItDoesNotReturnNullIfNoReturnType()
    {
        $functionMock = $this->getMockBuilder(ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $functionMock->method('getReturnType')->willReturn(null);
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertNotNull($basePHPElement->returnTypesFromSignature);
    }

    public function testItReturnsEmptyArrayIfNoReturnType()
    {
        $functionMock = $this->getMockBuilder(ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $functionMock->method('getReturnType')->willReturn(null);
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertTrue(is_array($basePHPElement->returnTypesFromSignature));;
        self::assertEmpty($basePHPElement->returnTypesFromSignature);
    }

    public function testItCanParseDeprecation()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $functionMock->method('isDeprecated')->willReturn(true);
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertTrue($basePHPElement->isDeprecated);
    }

    public function testItCanParseNonDeprecation()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $functionMock->method('isDeprecated')->willReturn(false);
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertFalse($basePHPElement->isDeprecated);
    }

    public function testItCanParseParameters()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $parameterMock = $this->getMockBuilder(\ReflectionParameter::class)->disableOriginalConstructor()->getMock();
        $parameterMock->method('getName')->willReturn('foo');
        $parameterMock->method('getType')->willReturn(null);
        $parameterMock->method('isOptional')->willReturn(false);
        $parameterMock->method('isPassedByReference')->willReturn(false);
        $parameterMock->method('isVariadic')->willReturn(false);
        $functionMock->method('getParameters')->willReturn([$parameterMock]);
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertNotNull($basePHPElement->getParameters());
    }

    public function testItReturnsCorrectAmountOfParameters()
    {
        $functionMock = $this->getMockBuilder(\ReflectionFunction::class)->disableOriginalConstructor()->getMock();
        $parameterMock = $this->getMockBuilder(\ReflectionParameter::class)->disableOriginalConstructor()->getMock();
        $parameterMock->method('getName')->willReturn('foo');
        $parameterMock->method('hasType')->willReturn(false);
        $parameterMock->method('getType')->willReturn(null);
        $parameterMock->method('isOptional')->willReturn(false);
        $parameterMock->method('isPassedByReference')->willReturn(false);
        $parameterMock->method('isVariadic')->willReturn(false);
        $functionMock->method('getParameters')->willReturn([$parameterMock]);
        $basePHPElement = new ReflectionObjectFunctionParser()->parse($functionMock);
        self::assertEquals(1, sizeof($basePHPElement->getParameters()));
    }
}
