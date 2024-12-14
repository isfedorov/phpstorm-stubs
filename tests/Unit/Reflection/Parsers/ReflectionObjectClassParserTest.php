<?php

namespace StubTests\Unit\Reflection\Parsers;

use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use StubTests\Sources\Model\Entities\PHPClass;
use StubTests\Sources\Model\EntitiesManagers\Additional\AdditionalManagerType;
use StubTests\Sources\Model\EntitiesManagers\ContainerManagersCollection;
use StubTests\Sources\Model\EntitiesManagers\EntityContainerManagerType;
use StubTests\Sources\Model\EntitiesManagers\ReflectionEntitiesContainerManager;
use StubTests\Sources\Model\StubsContainer;
use StubTests\Sources\Parsers\Entities\EntityType;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionObjectClassParser;

class ReflectionObjectClassParserTest extends BaseTestCase
{

    public function testItCanParseInternalClass()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(true);
        self::assertTrue(new ReflectionObjectClassParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseUsersClasses()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(false);
        self::assertFalse(new ReflectionObjectClassParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseInternalInterface()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(true);
        $stubReflectionClass->method('isInterface')->willReturn(true);
        self::assertFalse(new ReflectionObjectClassParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseUsersInterface()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(false);
        $stubReflectionClass->method('isInterface')->willReturn(true);
        self::assertFalse(new ReflectionObjectClassParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseInternalEnums()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(true);
        $stubReflectionClass->method('isEnum')->willReturn(true);
        self::assertFalse(new ReflectionObjectClassParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseUsersEnums()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(false);
        $stubReflectionClass->method('isEnum')->willReturn(true);
        self::assertFalse(new ReflectionObjectClassParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testParserIsApplicableToClasses()
    {
        self::assertTrue((new ReflectionObjectClassParser())->applicableTo(EntityType::A_CLASS));
    }

    public function testParserIsNotApplicableToInterfaces()
    {
        self::assertFalse((new ReflectionObjectClassParser())->applicableTo(EntityType::INTERFACE));
    }

    public function testParserIsNotApplicableToEnums()
    {
        self::assertFalse((new ReflectionObjectClassParser())->applicableTo(EntityType::ENUM));
    }

    public function testItReturnsCorrectInstance()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionMock);
        self::assertTrue($basePHPElement instanceof PHPClass);
    }

    public function testItCanParseName()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getShortName')->willReturn('Foo');
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionMock);
        self::assertEquals('Foo', $basePHPElement->name);
    }

    public function testItCanParseNamespace()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getNamespaceName')->willReturn('MyNameSpace\SubNameSpace');
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionMock);
        self::assertEquals('\MyNameSpace\SubNameSpace', $basePHPElement->namespace);
    }

    public function testItCanParseRootNamespace()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getNamespaceName')->willReturn('');
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionMock);
        self::assertEquals('', $basePHPElement->namespace);
    }

    public function testItCanParseId()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getShortName')->willReturn('SomeFooClass');
        $reflectionMock->method('getName')->willReturn('SomeFooClass');
        $reflectionMock->method('getNamespaceName')->willReturn('SomeNamespace\SubNamespace');
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionMock);
        self::assertEquals('\SomeNamespace\SubNamespace\SomeFooClass',$basePHPElement->id);
    }

    public function testItCanParseIdWithRootNamespace()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getShortName')->willReturn('SomeFooClass');
        $reflectionMock->method('getName')->willReturn('SomeFooClass');
        $reflectionMock->method('getNamespaceName')->willReturn('');
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionMock);
        self::assertEquals('\SomeFooClass',$basePHPElement->id);
    }

    public function testItCanParseFinalClass()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('isFinal')->willReturn(true);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionMock);
        self::assertTrue($basePHPElement->isFinal);
    }

    public function testItCanParseNonFinalClass()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('isFinal')->willReturn(false);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionMock);
        self::assertFalse($basePHPElement->isFinal);
    }

    public function testItCanParseReadonlyClass()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('isReadOnly')->willReturn(true);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionMock);
        self::assertTrue($basePHPElement->isReadonly);
    }

    public function testItCanParseNonReadonlyClass()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('isReadOnly')->willReturn(false);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionMock);
        self::assertFalse($basePHPElement->isReadonly);
    }

    public function testItCanParseMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('getName')->willReturn('foo');
        $reflectionClassMock->method('getMethods')->willReturn([$reflectionMethodMock]);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getMethods());
        self::assertNotEmpty($basePHPElement->getMethods());
    }

    public function testItCanParseProperties()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionPropertyMock = $this->getMockBuilder(ReflectionProperty::class)->disableOriginalConstructor()->getMock();
        $reflectionPropertyMock->method('getName')->willReturn('prop');
        $reflectionClassMock->method('getProperties')->willReturn([$reflectionPropertyMock]);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getProperties());
        self::assertNotEmpty($basePHPElement->getProperties());
    }

    public function testItCanParseClassConstantsPhp56()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassConstantMock = $this->getMockBuilder(ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('hasMethod')->with('getReflectionConstants')->willReturn(false);
        $reflectionClassMock->method('getConstants')->willReturn(['FOO' => "BAR"]);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getConstants());
        self::assertNotEmpty($basePHPElement->getConstants());
    }

    public function testItCanParseClassConstantsPhp71()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassConstantMock = $this->getMockBuilder(ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $reflectionClassConstantMock->method('getName')->willReturn('FOO');
        $reflectionClassConstantMock->method('getValue')->willReturn('BAR');
        $reflectionClassMock->method('hasMethod')->with('getReflectionConstants')->willReturn(true);
        $reflectionClassMock->method('getReflectionConstants')->willReturn([$reflectionClassConstantMock]);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getConstants());
        self::assertNotEmpty($basePHPElement->getConstants());
    }

    public function testItCanParseParentClass()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $parentClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getParentClass')->willReturn($parentClassMock);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getParentClass());
    }

    public function testItCanParseImplementedInterfaces()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionInterfaceMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getInterfaces')->willReturn([$reflectionInterfaceMock]);;
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getNotResolvedInterfaces());
        self::assertNotEmpty($basePHPElement->getNotResolvedInterfaces());
    }

    public function testItCanNotParsePhpDoc()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertNull($basePHPElement->getAdditionalManager(AdditionalManagerType::PhpDocManager));
    }

    public function testItCanNotParseStubsSpecificProperties()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertNull($basePHPElement->getAdditionalManager(AdditionalManagerType::StubsSpecificPropertiesManager));
    }

    public function testItDoesNotReturnNullIfNoMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getMethods')->willReturn([]);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getMethods());
    }

    public function testItReturnEmptyArrayIfNoMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getMethods')->willReturn([]);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertTrue(is_array($basePHPElement->getMethods()));
        self::assertEmpty($basePHPElement->getMethods());
    }

    public function testItReturnsCorrectNumberOfMethods()
    {
        $reflectionMethodMock = $this->getMockBuilder(ReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflectionMethodMock->method('getName')->willReturn('foo');
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('getDeclaringClass')->willReturn($reflectionClassMock);
        $reflectionClassMock->method('getMethods')->willReturn([$reflectionMethodMock]);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertEquals(1, sizeof($basePHPElement->getMethods()));
    }

    public function testItDoesNotReturnNullIfNoProperties()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getProperties')->willReturn([]);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getProperties());
    }

    public function testItReturnEmptyArrayIfNoProperties()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getProperties')->willReturn([]);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertTrue(is_array($basePHPElement->getProperties()));
        self::assertEmpty($basePHPElement->getProperties());
    }

    public function testItReturnsCorrectNumberOfProperties()
    {
        $reflectionPropertyMock = $this->getMockBuilder(ReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflectionPropertyMock->method('getName')->willReturn('prop');
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionPropertyMock->method('getDeclaringClass')->willReturn($reflectionClassMock);
        $reflectionClassMock->method('getProperties')->willReturn([$reflectionPropertyMock]);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertEquals(1, sizeof($basePHPElement->getProperties()));;
    }

    public function testItDoesNotReturnNullIfNoConstants()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getConstants')->willReturn([]);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getConstants());
    }

    public function testItReturnEmptyArrayIfNoConstants()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getConstants')->willReturn([]);
        $basePHPElement = new ReflectionObjectClassParser()->parse($reflectionClassMock);
        self::assertTrue(is_array($basePHPElement->getConstants()));
        self::assertEmpty($basePHPElement->getConstants());
    }

    public function testItCanAddParsedClassToContainer()
    {
        $containerManagerCollection = new ContainerManagersCollection();
        $containerManagerCollection->setManager(EntityContainerManagerType::ClassesManager, new ReflectionEntitiesContainerManager());
        $container = new StubsContainer($containerManagerCollection);;
        new ReflectionObjectClassParser()->parseAndAddToContainer(stdClass::class, $container);
        self::assertNotEmpty($container->getClasses());
    }

}
