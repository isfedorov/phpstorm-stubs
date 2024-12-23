<?php
declare(strict_types=1);

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use StubTests\Model\EntitiesProviders\EntitiesProvider;
use StubTests\Model\PhpVersions;
use StubTests\Model\Predicats\ClassesFilterPredicateProvider;
use StubTests\Model\Predicats\FunctionsFilterPredicateProvider;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use StubTests\TestData\Providers\Reflection\ReflectionClassesTestDataProviders;
use StubTests\TestData\Providers\Reflection\ReflectionMethodsProvider;
use StubTests\TestData\Providers\Reflection\ReflectionPropertiesProvider;
use StubTests\TestData\Providers\ReflectionStubsSingleton;

class BaseClassesTest extends AbstractBaseStubsTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        PhpStormStubsSingleton::getPhpStormStubs();
        ReflectionStubsSingleton::getReflectionStubs();
    }

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'classWithParentProvider')]
    public function testClassesParent(?string $classId)
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $stubClass = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        static::assertEquals(
            $reflectionClass->parentClass,
            $stubClass->parentClass,
            empty($reflectionClass->parentClass) ? "Class $classId should not extend $stubClass->parentClass" :
                "Class $classId should extend $reflectionClass->parentClass"
        );
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'classMethodsProvider')]
    public function testClassesMethodsExist(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubClass = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        static::assertNotEmpty($stubClass->getMethod(FunctionsFilterPredicateProvider::getMethodsByName($methodName)), "Missing method $classId::$methodName");
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'classFinalMethodsProvider')]
    public function testClassesFinalMethods(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $reflectionMethod = $reflectionClass->getMethod(FunctionsFilterPredicateProvider::getMethodsByName($methodName));
        $stubMethod = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId))->getMethod(FunctionsFilterPredicateProvider::getMethodsByName($methodName));
        static::assertEquals(
            $reflectionMethod->isFinal,
            $stubMethod->isFinal,
            "Method $classId::$methodName final modifier is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'classStaticMethodsProvider')]
    public function testClassesStaticMethods(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $reflectionMethod = $reflectionClass->getMethod(FunctionsFilterPredicateProvider::getMethodsByName($methodName));
        $stubMethod = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), $classId)->getMethod(FunctionsFilterPredicateProvider::getMethodsByName($methodName));
        static::assertEquals(
            $reflectionMethod->isStatic,
            $stubMethod->isStatic,
            "Method $classId::$methodName static modifier is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'classMethodsWithAccessProvider')]
    public function testClassesMethodsVisibility(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $stubMethod = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId))->getMethod(FunctionsFilterPredicateProvider::getMethodsByName($methodName));
        static::assertEquals(
            $reflectionMethod->access,
            $stubMethod->access,
            "Method $classId::$methodName access modifier is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionMethodsProvider::class, 'classMethodsWithParametersProvider')]
    public function testClassMethodsParametersCount(?string $classId, ?string $methodName)
    {
        if (!$classId && !$methodName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $reflectionMethod = $reflectionClass->getMethod(FunctionsFilterPredicateProvider::getMethodsByName($methodName));
        $stubMethod = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId))->getMethod(FunctionsFilterPredicateProvider::getMethodsByName($methodName));
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

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'classesWithInterfacesProvider')]
    public function testClassInterfaces(?string $classId)
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $stubClass = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        foreach ($reflectionClass->interfaces as $interface) {
            static::assertContains(
                $interface,
                $stubClass->interfaces,
                "Class $classId doesn't implement interface $interface"
            );
        }
    }

    #[DataProviderExternal(ReflectionPropertiesProvider::class, 'classPropertiesProvider')]
    public function testClassProperties(?string $classId, ?string $propertyName)
    {
        if (!$classId && !$propertyName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $reflectionProperty = $reflectionClass->getProperty($propertyName);
        $stubClass = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        static::assertNotEmpty($stubClass->getProperty($propertyName), "Missing property $reflectionProperty->access "
            . implode('|', $reflectionProperty->typesFromSignature) .
            "$classId::$$propertyName");
    }

    #[DataProviderExternal(ReflectionPropertiesProvider::class, 'classStaticPropertiesProvider')]
    public function testClassStaticProperties(?string $classId, ?string $propertyName)
    {
        if (!$classId && !$propertyName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $reflectionProperty = $reflectionClass->getProperty($propertyName);
        $stubProperty = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId))->getProperty($propertyName);
        static::assertEquals(
            $reflectionProperty->is_static,
            $stubProperty->is_static,
            "Property $classId::$propertyName static modifier is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionPropertiesProvider::class, 'classPropertiesWithAccessProvider')]
    public function testClassPropertiesVisibility(?string $classId, ?string $propertyName)
    {
        if (!$classId && !$propertyName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $reflectionProperty = $reflectionClass->getProperty($propertyName);
        $stubProperty = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId))->getProperty($propertyName);
        static::assertEquals(
            $reflectionProperty->access,
            $stubProperty->access,
            "Property $classId::$propertyName access modifier is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionPropertiesProvider::class, 'classPropertiesWithTypeProvider')]
    public function testClassPropertiesType(?string $classId, ?string $propertyName)
    {
        if (!$classId && !$propertyName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $reflectionProperty = $reflectionClass->getProperty($propertyName);
        $stubProperty = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId))->getProperty($propertyName);
        $unifiedStubsPropertyTypes = [];
        $unifiedStubsAttributesPropertyTypes = [];
        $unifiedReflectionPropertyTypes = [];
        self::convertNullableTypesToUnion($reflectionProperty->typesFromSignature, $unifiedReflectionPropertyTypes);
        if (!empty($stubProperty->typesFromSignature)) {
            self::convertNullableTypesToUnion($stubProperty->typesFromSignature, $unifiedStubsPropertyTypes);
        }
        foreach ($stubProperty->typesFromAttribute as $languageVersion => $listOfTypes) {
            $unifiedStubsAttributesPropertyTypes[$languageVersion] = [];
            self::convertNullableTypesToUnion($listOfTypes, $unifiedStubsAttributesPropertyTypes[$languageVersion]);
        }
        $typesFromAttribute = [];
        $testCondition = self::isReflectionTypesMatchSignature($unifiedReflectionPropertyTypes, $unifiedStubsPropertyTypes);
        if (!$testCondition) {
            if (!empty($unifiedStubsAttributesPropertyTypes)) {
                $typesFromAttribute = !empty($unifiedStubsAttributesPropertyTypes[getenv('PHP_VERSION')]) ?
                    $unifiedStubsAttributesPropertyTypes[getenv('PHP_VERSION')] :
                    $unifiedStubsAttributesPropertyTypes['default'];
                $testCondition = self::isReflectionTypesExistInAttributes($unifiedReflectionPropertyTypes, $typesFromAttribute);
            }
        }
        self::assertTrue($testCondition, "Property $classId::$propertyName has invalid typehint.
        Reflection property has type " . implode('|', $unifiedReflectionPropertyTypes) . ' but stubs has type ' .
            implode('|', $unifiedStubsPropertyTypes) . ' in signature and attribute has types ' .
            implode('|', $typesFromAttribute));
    }

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'allClassesProvider')]
    public function testClassesExist(?string $classId): void
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $class = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        static::assertNotEmpty($class, "Missing class $classId: class $class->name {}");
    }

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'finalClassesProvider')]
    public function testClassesFinal(?string $classId): void
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $stubClass = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        static::assertEquals($reflectionClass->isFinal, $stubClass->isFinal, "Final modifier of class $classId is incorrect");
    }

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'readonlyClassesProvider')]
    public function testClassesReadonly(?string $classId): void
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $stubClass = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        static::assertEquals(
            $reflectionClass->isReadonly,
            $stubClass->isReadonly,
            "Readonly modifier for class $classId is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionClassesTestDataProviders::class, 'classWithNamespaceProvider')]
    public function testClassesNamespace(?string $classId): void
    {
        if (!$classId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $stubClass = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        static::assertEquals(
            $reflectionClass->namespace,
            $stubClass->namespace,
            "Namespace for class $classId is incorrect"
        );
    }
}
