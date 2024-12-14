<?php

namespace StubTests\Unit\Reflection\Parsers;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use StubTests\Sources\Model\Entities\PHPInterface;
use StubTests\Sources\Model\EntitiesManagers\Additional\AdditionalManagerType;
use StubTests\Sources\Model\EntitiesManagers\ContainerManagersCollection;
use StubTests\Sources\Model\EntitiesManagers\EntityContainerManagerType;
use StubTests\Sources\Model\EntitiesManagers\ReflectionEntitiesContainerManager;
use StubTests\Sources\Model\StubsContainer;
use StubTests\Sources\Parsers\Entities\EntityType;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionObjectInterfaceParser;
use Traversable;
use function PHPUnit\Framework\assertNotNull;

class ReflectionObjectInterfaceParserTest extends TestCase
{
    public function testItCanParseInternalInterface()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(true);
        $stubReflectionClass->method('isInterface')->willReturn(true);
        self::assertTrue(new ReflectionObjectInterfaceParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseUserInterface()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(false);
        $stubReflectionClass->method('isInterface')->willReturn(true);
        self::assertFalse(new ReflectionObjectInterfaceParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseInternalNonInterface()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(true);
        $stubReflectionClass->method('isInterface')->willReturn(false);
        self::assertFalse(new ReflectionObjectInterfaceParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseUsersNonInterface()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(false);
        $stubReflectionClass->method('isInterface')->willReturn(false);
        self::assertFalse(new ReflectionObjectInterfaceParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseInternalEnums()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(true);
        $stubReflectionClass->method('isEnum')->willReturn(true);
        self::assertFalse(new ReflectionObjectInterfaceParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItCanNotParseUsersEnums()
    {
        $stubReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubReflectionClass->method('isInternal')->willReturn(false);
        $stubReflectionClass->method('isEnum')->willReturn(true);
        self::assertFalse(new ReflectionObjectInterfaceParser()->canParseReflectionClass($stubReflectionClass));
    }

    public function testItIsApplicableToInterfaces()
    {
        self::assertTrue((new ReflectionObjectInterfaceParser())->applicableTo(EntityType::INTERFACE));
    }

    public function testItIsNotApplicableToClasses()
    {
        self::assertFalse((new ReflectionObjectInterfaceParser())->applicableTo(EntityType::A_CLASS));
    }

    public function testItIsNotApplicableToEnums()
    {
        self::assertFalse((new ReflectionObjectInterfaceParser())->applicableTo(EntityType::ENUM));
    }

    public function testItReturnsCorrectInstance()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionMock);
        self::assertTrue($basePHPElement instanceof PHPInterface);
    }

    public function testItCanParseName()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getShortName')->willReturn('Foo');
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionMock);
        self::assertEquals('Foo', $basePHPElement->name);
    }

    public function testItCanParseNamespace()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getNamespaceName')->willReturn('MyNameSpace\SubNameSpace');
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionMock);
        self::assertEquals('\MyNameSpace\SubNameSpace', $basePHPElement->namespace);
    }

    public function testItCanParseRootNamespace()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getNamespaceName')->willReturn('');
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionMock);
        self::assertEquals('', $basePHPElement->namespace);
    }

    public function testItCanParseId()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getShortName')->willReturn('SomeFooClass');
        $reflectionMock->method('getName')->willReturn('SomeFooClass');
        $reflectionMock->method('getNamespaceName')->willReturn('SomeNamespace\SubNamespace');
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionMock);
        self::assertEquals('\SomeNamespace\SubNamespace\SomeFooClass',$basePHPElement->id);
    }

    public function testItCanParseIdWithRootNamespace()
    {
        $reflectionMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMock->method('getShortName')->willReturn('SomeFooClass');
        $reflectionMock->method('getName')->willReturn('SomeFooClass');
        $reflectionMock->method('getNamespaceName')->willReturn('');
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionMock);
        self::assertEquals('\SomeFooClass',$basePHPElement->id);
    }

    public function testItCanParseMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock = $this->getMockBuilder(\ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $reflectionMethodMock->method('getName')->willReturn('foo');
        $reflectionClassMock->method('getMethods')->willReturn([$reflectionMethodMock]);
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getMethods());
        self::assertNotEmpty($basePHPElement->getMethods());
    }

    public function testItCanParseConstants()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('hasMethod')->with('getReflectionConstants')->willReturn(true);
        $reflectionClassConstantsMock = $this->getMockBuilder(\ReflectionClassConstant::class)->disableOriginalConstructor()->getMock();
        $reflectionClassConstantsMock->method('getName')->willReturn('FOO');
        $reflectionClassConstantsMock->method('getValue')->willReturn('BAR');
        $reflectionClassMock->method('getReflectionConstants')->willReturn([$reflectionClassConstantsMock]);
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getConstants());
        self::assertNotEmpty($basePHPElement->getConstants());
    }

    public function testItContainsParentInterfaceManager()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $basePhpElement = new ReflectionObjectInterfaceParser()->parse($reflectionClassMock);
        assertNotNull($basePhpElement->getAdditionalManager(AdditionalManagerType::ParentInterfacesManager));
    }

    public function testItDoesNotContainParentClassManager()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionClassMock);
        self::assertNull($basePHPElement->getAdditionalManager(AdditionalManagerType::ParentClassManager));
    }

    public function testItDoesNotContainPhpDocManager()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionClassMock);
        self::assertNull($basePHPElement->getAdditionalManager(AdditionalManagerType::PhpDocManager));
    }

    public function testItDoesNotContainStubsSpesificManager()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionClassMock);
        self::assertNull($basePHPElement->getAdditionalManager(AdditionalManagerType::StubsSpecificPropertiesManager));
    }

    public function testItDoesNotReturnNullIfNoMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getMethods')->willReturn([]);
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getMethods());
    }

    public function testItReturnsEmptyArrayIfNoMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getMethods')->willReturn([]);
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionClassMock);
        self::assertTrue(is_array($basePHPElement->getMethods()));;
        self::assertEmpty($basePHPElement->getMethods());
    }

    public function testItCanParseInterfaceMethods()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $methodMock = $this->getMockBuilder(\ReflectionMethod::class)->disableOriginalConstructor()->getMock();
        $methodMock->method('getName')->willReturn('foo');
        $methodMock->method('getDeclaringClass')->willReturn($reflectionClassMock);
        $reflectionClassMock->method('getMethods')->willReturn([$methodMock]);
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionClassMock);
        self::assertEquals(1, sizeof($basePHPElement->getMethods()));
    }

    public function testItDoesNotReturnNullIfNoConstants()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getConstants')->willReturn([]);
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionClassMock);
        self::assertNotNull($basePHPElement->getConstants());
    }

    public function testItReturnsEmptyArrayIfNoConstants()
    {
        $reflectionClassMock = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $reflectionClassMock->method('getConstants')->willReturn([]);
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionClassMock);
        self::assertTrue(is_array($basePHPElement->getConstants()));;
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
        $basePHPElement = new ReflectionObjectInterfaceParser()->parse($reflectionClassMock);
        self::assertEquals(1, sizeof($basePHPElement->getConstants()));
    }

    public function testItCanAddParsedClassToContainer()
    {
        $containerManagerCollection = new ContainerManagersCollection();
        $containerManagerCollection->setManager(EntityContainerManagerType::InterfacesManager, new ReflectionEntitiesContainerManager());
        $container = new StubsContainer($containerManagerCollection);
        new ReflectionObjectInterfaceParser()->parseAndAddToContainer(Traversable::class, $container);
        self::assertNotEmpty($container->getInterfaces());
    }
}
