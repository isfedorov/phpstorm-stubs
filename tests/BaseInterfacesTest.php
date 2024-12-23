<?php

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use StubTests\Model\EntitiesProviders\EntitiesProvider;
use StubTests\Model\PhpVersions;
use StubTests\Model\Predicats\FunctionsFilterPredicateProvider;
use StubTests\Model\Predicats\InterfaceFilterPredicateProvider;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use StubTests\TestData\Providers\Reflection\ReflectionClassesTestDataProviders;
use StubTests\TestData\Providers\Reflection\ReflectionMethodsProvider;
use StubTests\TestData\Providers\ReflectionStubsSingleton;

class BaseInterfacesTest extends AbstractBaseStubsTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        PhpStormStubsSingleton::getPhpStormStubs();
        ReflectionStubsSingleton::getReflectionStubs();
    }

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'interfaceWithParentProvider')]
    public function testInterfacesParent(?string $interfaceId)
    {
        if (!$interfaceId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionInterface = EntitiesProvider::getInterface(ReflectionStubsSingleton::getReflectionStubs(), InterfaceFilterPredicateProvider::getInterfaceById($interfaceId));
        $stubInterface = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($interfaceId));
        foreach ($reflectionInterface->parentInterfaces as $parentInterface) {
            static::assertContains(
                $parentInterface,
                $stubInterface->parentInterfaces,
                "Interface $interfaceId should extend $parentInterface"
            );
        }
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'interfaceMethodsProvider')]
    public function testInterfacesMethodsExist(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubClass = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        static::assertNotEmpty($stubClass->getMethod($methodName), "Missing method $classId::$methodName");
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'interfaceFinalMethodsProvider')]
    public function testInterfacesFinalMethods(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionInterface = EntitiesProvider::getInterface(ReflectionStubsSingleton::getReflectionStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        $reflectionMethod = $reflectionInterface->getMethod($methodName);
        $stubMethod = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId))->getMethod($methodName);
        static::assertEquals(
            $reflectionMethod->isFinal,
            $stubMethod->isFinal,
            "Method $classId::$methodName final modifier is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'interfaceStaticMethodsProvider')]
    public function testInterfacesStaticMethods(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionInterface = EntitiesProvider::getInterface(ReflectionStubsSingleton::getReflectionStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        $reflectionMethod = $reflectionInterface->getMethod($methodName);
        $stubMethod = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId))->getMethod($methodName);
        static::assertEquals(
            $reflectionMethod->isStatic,
            $stubMethod->isStatic,
            "Method $classId::$methodName static modifier is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'interfaceMethodsWithAccessProvider')]
    public function testInterfacesMethodsVisibility(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionInterface = EntitiesProvider::getInterface(ReflectionStubsSingleton::getReflectionStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        $reflectionMethod = $reflectionInterface->getMethod($methodName);
        $stubMethod = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId))->getMethod($methodName);
        static::assertEquals(
            $reflectionMethod->access,
            $stubMethod->access,
            "Method $classId::$methodName access modifier is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'interfaceMethodsWithParametersProvider')]
    public function testInterfaceMethodsParametersCount(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionInterface = EntitiesProvider::getInterface(ReflectionStubsSingleton::getReflectionStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        $reflectionMethod = $reflectionInterface->getMethod($methodName);
        $stubMethod = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId))->getMethod($methodName);
        $filteredStubParameters = array_filter(
            $stubMethod->parameters,
            function ($parameter) {
                if (!empty($parameter->getOrCreateStubSpecificProperties()->availableVersionsRangeFromAttribute)) {
                    return $parameter->getOrCreateStubSpecificProperties()->availableVersionsRangeFromAttribute['from'] <= (doubleval(getenv('PHP_VERSION') ?? PhpVersions::getFirst()))
                        && $parameter->getOrCreateStubSpecificProperties()->availableVersionsRangeFromAttribute['to'] >= (doubleval(getenv('PHP_VERSION')) ?? PhpVersions::getLatest());
                } else {
                    return true;
                }
            }
        );
        static::assertSameSize(
            $reflectionMethod->parameters,
            $filteredStubParameters,
            "Parameter number mismatch for method $classId::$methodName.
                         Expected: " . self::getParameterRepresentation($reflectionMethod)
        );
    }

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'allInterfacesProvider')]
    public function testInterfacesExist(?string $classId): void
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $class = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        static::assertNotEmpty($class, "Missing interface $classId: interface $class->name {}");
    }

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'finalInterfacesProvider')]
    public function testInterfacesFinal(?string $classId): void
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getInterface(ReflectionStubsSingleton::getReflectionStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        $stubClass = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        static::assertEquals($reflectionClass->isFinal, $stubClass->isFinal, "Final modifier of interface $classId is incorrect");
    }

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'interfaceWithNamespaceProvider')]
    public function testInterfacesNamespace(?string $classId): void
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getInterface(ReflectionStubsSingleton::getReflectionStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        $stubClass = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        static::assertEquals(
            $reflectionClass->namespace,
            $stubClass->namespace,
            "Namespace for interface $classId is incorrect"
        );
    }
}
