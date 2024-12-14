<?php

namespace StubTests\Unit\Reflection\Parsers;

use PHPUnit\Framework\TestCase;
use PropertyHookType;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionEnum;
use StubTests\Sources\Model\Entities\PHPEnum;
use StubTests\Sources\Model\EntitiesManagers\Additional\AdditionalManagerType;
use StubTests\Sources\Model\EntitiesManagers\ContainerManagersCollection;
use StubTests\Sources\Model\EntitiesManagers\EntityContainerManagerType;
use StubTests\Sources\Model\EntitiesManagers\ReflectionEntitiesContainerManager;
use StubTests\Sources\Model\StubsContainer;
use StubTests\Sources\Parsers\Entities\EntityType;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionObjectEnumParser;

class ReflectionObjectEnumParserTest extends TestCase
{
    public function testItCanParseInternalEnums()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(true);
        $stubReflectionClass->method('isEnum')->willReturn(true);
        self::assertTrue(new ReflectionObjectEnumParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseUserEnums()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(false);
        $stubReflectionClass->method('isEnum')->willReturn(true);
        self::assertFalse(new ReflectionObjectEnumParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseInternalNonEnum()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(true);
        $stubReflectionClass->method('isEnum')->willReturn(false);
        self::assertFalse(new ReflectionObjectEnumParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseUsersNonEnum()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(false);
        $stubReflectionClass->method('isEnum')->willReturn(false);
        self::assertFalse(new ReflectionObjectEnumParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItIsApplicableToEnums()
    {
        self::assertTrue((new ReflectionObjectEnumParser())->applicableTo(EntityType::ENUM));
    }

    public function testItIsNotApplicableToInterfaces()
    {
        self::assertFalse((new ReflectionObjectEnumParser())->applicableTo(EntityType::INTERFACE));
    }

    public function testItIsNotApplicableToClasses()
    {
        self::assertFalse((new ReflectionObjectEnumParser())->applicableTo(EntityType::A_CLASS));
    }

    public function testItReturnsCorrectInstanceOfEnum()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionMock);
        self::assertTrue($basePHPElement instanceof PHPEnum);
    }

    public function testItCanParseName()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getShortName')->willReturn('Foo');
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionMock);
        self::assertEquals('Foo', $basePHPElement->name);
    }

    public function testItCanParseNamespace()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getNamespaceName')->willReturn('MyNameSpace\SubNameSpace');
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionMock);
        self::assertEquals('\MyNameSpace\SubNameSpace', $basePHPElement->namespace);
    }

    public function testItCanParseRootNamespace()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getNamespaceName')->willReturn('');
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionMock);
        self::assertEquals('', $basePHPElement->namespace);
    }

    public function testItCanParseId()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getShortName')->willReturn('SomeFooClass');
        $reflectionMock->method('getName')->willReturn('SomeFooClass');
        $reflectionMock->method('getNamespaceName')->willReturn('SomeNamespace\SubNamespace');
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionMock);
        self::assertEquals('\SomeNamespace\SubNamespace\SomeFooClass',$basePHPElement->id);
    }

    public function testItCanParseIdWithRootNamespace()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getShortName')->willReturn('SomeFooClass');
        $reflectionMock->method('getName')->willReturn('SomeFooClass');
        $reflectionMock->method('getNamespaceName')->willReturn('');
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionMock);
        self::assertEquals('\SomeFooClass',$basePHPElement->id);
    }

    public function testItCanParseFinalEnum()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('isFinal')->willReturn(true);
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionMock);
        self::assertTrue($basePHPElement->isFinal);
    }

    public function testItCanParseNonFinalEnum()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('isFinal')->willReturn(false);
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionMock);
        self::assertFalse($basePHPElement->isFinal);
    }

    public function testItCanParseReadonlyEnum()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('isReadOnly')->willReturn(true);
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionMock);
        self::assertTrue($basePHPElement->isReadonly);
    }

    public function testItCanParseNonReadonlyEnum()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('isReadOnly')->willReturn(false);
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionMock);
        self::assertFalse($basePHPElement->isReadonly);
    }

    public function testItCanParseMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock = $this->getMockBuilder(\ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('getName')->willReturn('someMethod');
        $reflectionClassMock->method('getMethods')->willReturn([$reflectionMethodMock]);
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getMethods());
        self::assertNotEmpty($basePHPElement->getMethods());
    }

    public function testItCanParseClassConstantsPhp56()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionClassConstantMock = $this->getMockBuilder(ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('hasMethod')->with('getReflectionConstants')->willReturn(false);
        $reflectionClassMock->method('getConstants')->willReturn(['FOO' => "BAR"]);
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getConstants());
        self::assertNotEmpty($basePHPElement->getConstants());
    }

    public function testItCanParseClassConstantsPhp71()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionClassConstantMock = $this->getMockBuilder(ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $reflectionClassConstantMock->method('getName')->willReturn('FOO');
        $reflectionClassConstantMock->method('getValue')->willReturn('BAR');
        $reflectionClassMock->method('hasMethod')->with('getReflectionConstants')->willReturn(true);
        $reflectionClassMock->method('getReflectionConstants')->willReturn([$reflectionClassConstantMock]);
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getConstants());
        self::assertNotEmpty($basePHPElement->getConstants());
    }

    public function testItCanNotParseProperties()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionClassMock);
        self::assertNull($basePHPElement->getAdditionalManager(AdditionalManagerType::PropertiesManager));
    }

    public function testItCanNotParseParentClass()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionClassMock);
        self::assertNull($basePHPElement->getAdditionalManager(AdditionalManagerType::ParentClassManager));
    }
    public function testItCanParseImplementedInterfaces()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $implementedInterfaceMock = $this->getMockBuilder(\ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $implementedInterfaceMock->method('getName')->willReturn('Foo');
        $reflectionClassMock->method('getInterfaces')->willReturn([$implementedInterfaceMock]);
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getNotResolvedInterfaces());
        self::assertNotEmpty($basePHPElement->getNotResolvedInterfaces());
    }

    public function testItDoesNotContainPhpDocManager()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionClassMock);
        self::assertFalse($basePHPElement->canGetDataFromPhpDoc());
    }

    public function testItDoesNotContainStubsSpecificManager()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionClassMock);
        self::assertNull($basePHPElement->getAdditionalManager(AdditionalManagerType::StubsSpecificPropertiesManager));
    }

    public function testItDoesNotReturnNullIfNoCases()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getCases')->willReturn([]);
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getCases());
    }

    public function testItReturnsEmptyArrayIfNoCases()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionEnum::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getCases')->willReturn([]);
        $basePHPElement = new ReflectionObjectEnumParser()->parse($reflectionClassMock);
        self::assertTrue(is_array($basePHPElement->getCases()));;
        self::assertEmpty($basePHPElement->getCases());
    }

    public function testItCanParseEnumValues()
    {
        $parsedEnum = new ReflectionObjectEnumParser()->parse(new ReflectionEnum(PropertyHookType::class));
        self::assertEquals(2, sizeof($parsedEnum->getCases()));
    }

    public function testItCanAddParsedClassToContainer()
    {
        $containerManagerCollection = new ContainerManagersCollection();
        $containerManagerCollection->setManager(EntityContainerManagerType::EnumsManager, new ReflectionEntitiesContainerManager());
        $container = new StubsContainer($containerManagerCollection);;
        new ReflectionObjectEnumParser()->parseAndAddToContainer(PropertyHookType::class, $container);
        self::assertNotEmpty($container->getEnums());
    }
}
