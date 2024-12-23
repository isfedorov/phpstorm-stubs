<?php
declare(strict_types=1);

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use StubTests\Model\EntitiesProviders\EntitiesProvider;
use StubTests\Model\Predicats\ClassesFilterPredicateProvider;
use StubTests\Model\Predicats\ConstantsFilterPredicateProvider;
use StubTests\Model\Predicats\EnumsFilterPredicateProvider;
use StubTests\Model\Predicats\InterfaceFilterPredicateProvider;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use StubTests\TestData\Providers\Reflection\ReflectionConstantsProvider;
use StubTests\TestData\Providers\ReflectionStubsSingleton;

class BaseConstantsTest extends AbstractBaseStubsTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        PhpStormStubsSingleton::getPhpStormStubs();
        ReflectionStubsSingleton::getReflectionStubs();
    }

    #[DataProviderExternal(ReflectionConstantsProvider::class, 'constantProvider')]
    public function testConstants(?string $constantId): void
    {
        if (!$constantId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionConstant = EntitiesProvider::getConstant(ReflectionStubsSingleton::getReflectionStubs(), ConstantsFilterPredicateProvider::getConstantById($constantId));
        $constantValue = $reflectionConstant->value;
        $stubConstant = EntitiesProvider::getConstant(PhpStormStubsSingleton::getPhpStormStubs(), ConstantsFilterPredicateProvider::getConstantById($constantId));
        static::assertNotEmpty(
            $stubConstant,
            "Missing constant: const $constantId = $constantValue\n"
        );
    }

    #[DataProviderExternal(ReflectionConstantsProvider::class, 'classConstantProvider')]
    public function testClassConstants(?string $classId, ?string $constantName): void
    {
        if (!$classId && !$constantName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $reflectionConstant = $reflectionClass->getConstant($constantName);
        $constantValue = $reflectionConstant->value;
        $stubConstant = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId))->getConstant($constantName);
        static::assertNotEmpty(
            $stubConstant,
            "Missing constant: $classId::$constantName = $constantValue\n"
        );
    }

    #[DataProviderExternal(ReflectionConstantsProvider::class, 'interfaceConstantProvider')]
    public function testInterfaceConstants(?string $classId, ?string $constantName): void
    {
        if (!$classId && !$constantName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectedInterface = EntitiesProvider::getInterface(ReflectionStubsSingleton::getReflectionStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        $reflectionConstant = $reflectedInterface->getConstant($constantName);
        $constantValue = $reflectionConstant->value;
        $stubConstant = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId))->getConstant($constantName);
        static::assertNotEmpty(
            $stubConstant,
            "Missing constant: $classId::$constantName = $constantValue\n"
        );
    }

    #[DataProviderExternal(ReflectionConstantsProvider::class, 'classConstantProvider')]
    public function testClassConstantsVisibility(?string $classId, ?string $constantName): void
    {
        if (!$classId && !$constantName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $reflectionConstant = $reflectionClass->getConstant($constantName, ConstantsFilterPredicateProvider::getClassConstantsFromReflection($constantName));
        $constantVisibility = $reflectionConstant->visibility;
        $stubConstant = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId))->getConstant($constantName);
        static::assertEquals(
            $constantVisibility,
            $stubConstant->visibility,
            "Constant visibility mismatch: const $constantName \n
            Expected visibility: $constantVisibility but was $stubConstant->visibility"
        );
    }

    #[DataProviderExternal(ReflectionConstantsProvider::class, 'interfaceConstantProvider')]
    public function testInterfaceConstantsVisibility(?string $classId, ?string $constantName): void
    {
        if (!$classId && !$constantName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionInterface = EntitiesProvider::getInterface(ReflectionStubsSingleton::getReflectionStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        $reflectionConstant = $reflectionInterface->getConstant($constantName);
        $constantVisibility = $reflectionConstant->visibility;
        $stubConstant = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId))->getConstant($constantName);
        static::assertEquals(
            $constantVisibility,
            $stubConstant->visibility,
            "Constant visibility mismatch: const $constantName \n
            Expected visibility: $constantVisibility but was $stubConstant->visibility"
        );
    }

    #[DataProviderExternal(ReflectionConstantsProvider::class, 'enumConstantProvider')]
    public function testEnumConstantsVisibility(?string $classId, ?string $constantName): void
    {
        if (!$classId && !$constantName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionEnum = EntitiesProvider::getEnum(ReflectionStubsSingleton::getReflectionStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        $reflectionConstant = $reflectionEnum->getConstant($constantName);
        $constantVisibility = $reflectionConstant->visibility;
        $stubConstant = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($classId))->getConstant($constantName);
        static::assertEquals(
            $constantVisibility,
            $stubConstant->visibility,
            "Constant visibility mismatch: const $constantName \n
            Expected visibility: $constantVisibility but was $stubConstant->visibility"
        );
    }
}
