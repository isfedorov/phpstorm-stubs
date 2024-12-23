<?php

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use StubTests\Model\EntitiesProviders\EntitiesProvider;
use StubTests\Model\PhpVersions;
use StubTests\Model\Predicats\EnumsFilterPredicateProvider;
use StubTests\Model\Predicats\FunctionsFilterPredicateProvider;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use StubTests\TestData\Providers\Reflection\ReflectionClassesTestDataProviders;
use StubTests\TestData\Providers\Reflection\ReflectionMethodsProvider;
use StubTests\TestData\Providers\ReflectionStubsSingleton;

class BaseEnumsTest extends AbstractBaseStubsTestCase
{
    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'enumWithParentProvider')]
    public function testEnumsParent(?string $enumId)
    {
        if (!$enumId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionEnum = EntitiesProvider::getEnum(ReflectionStubsSingleton::getReflectionStubs(), EnumsFilterPredicateProvider::getEnumById($enumId));
        $stubEnum = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($enumId));
        static::assertEquals(
            $reflectionEnum->parentClass,
            $stubEnum->parentClass,
            empty($reflectionEnum->parentClass) ? "Enum $enumId should not extend $stubEnum->parentClass" :
                "Enum $enumId should extend $reflectionEnum->parentClass"
        );
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'enumMethodsProvider')]
    public function testEnumsMethodsExist(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubClass = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        static::assertNotEmpty($stubClass->getMethod($methodName), "Missing method $classId::$methodName");
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'enumFinalMethodsProvider')]
    public function testEnumsFinalMethods(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionEnum = EntitiesProvider::getEnum(ReflectionStubsSingleton::getReflectionStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        $reflectionMethod = $reflectionEnum->getMethod($methodName);
        $stubMethod = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($classId))->getMethod($methodName);
        static::assertEquals(
            $reflectionMethod->isFinal,
            $stubMethod->isFinal,
            "Method $classId::$methodName final modifier is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'enumStaticMethodsProvider')]
    public function testEnumsStaticMethods(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionEnum = EntitiesProvider::getEnum(ReflectionStubsSingleton::getReflectionStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        $reflectionMethod = $reflectionEnum->getMethod($methodName);
        $stubMethod = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($classId))->getMethod($methodName);
        static::assertEquals(
            $reflectionMethod->isStatic,
            $stubMethod->isStatic,
            "Method $classId::$methodName static modifier is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'enumMethodsWithAccessProvider')]
    public function testEnumsMethodsVisibility(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionEnum = EntitiesProvider::getEnum(ReflectionStubsSingleton::getReflectionStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        $reflectionMethod = $reflectionEnum->getMethod($methodName);
        $stubMethod = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($classId))->getMethod($methodName);
        static::assertEquals(
            $reflectionMethod->access,
            $stubMethod->access,
            "Method $classId::$methodName access modifier is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'enumMethodsWithParametersProvider')]
    public function testEnumMethodsParametersCount(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionEnum = EntitiesProvider::getEnum(ReflectionStubsSingleton::getReflectionStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        $reflectionMethod = $reflectionEnum->getMethod($methodName);
        $stubMethod = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($classId))->getMethod($methodName);
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

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'enumsWithInterfacesProvider')]
    public function testEnumsInterfaces(?string $classId)
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getEnum(ReflectionStubsSingleton::getReflectionStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        $stubClass = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        foreach ($reflectionClass->interfaces as $interface) {
            static::assertContains(
                $interface,
                $stubClass->interfaces,
                "Enum $classId doesn't implement interface $interface"
            );
        }
    }

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'allEnumsProvider')]
    public function testEnumsExist(?string $classId): void
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $class = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        static::assertNotEmpty($class, "Missing enum $classId: enum $class->name {}");
    }

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'finalEnumsProvider')]
    public function testEnumsFinal(?string $classId): void
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getEnum(ReflectionStubsSingleton::getReflectionStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        $stubClass = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        static::assertEquals($reflectionClass->isFinal, $stubClass->isFinal, "Final modifier of enum $classId is incorrect");
    }

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'enumsWithNamespaceProvider')]
    public function testEnumsNamespace(?string $classId): void
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getEnum(ReflectionStubsSingleton::getReflectionStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        $stubClass = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        static::assertEquals(
            $reflectionClass->namespace,
            $stubClass->namespace,
            "Namespace for enum $classId is incorrect"
        );
    }
}
