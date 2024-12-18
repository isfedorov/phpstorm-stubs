<?php
declare(strict_types=1);

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use StubTests\Model\Predicats\ConstantsFilterPredicateProvider;
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
        $reflectionConstant = ReflectionStubsSingleton::getReflectionStubs()->getConstant($constantId, sourceFilePath: true, fromReflection: true);
        $constantValue = $reflectionConstant->value;
        $stubConstant = PhpStormStubsSingleton::getPhpStormStubs()->getConstant($constantId);
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
        $reflectionClass = ReflectionStubsSingleton::getReflectionStubs()->getClass($classId);
        $reflectionConstant = $reflectionClass->getConstant($constantName, ConstantsFilterPredicateProvider::getConstantsFromReflection($constantName));
        $constantValue = $reflectionConstant->value;
        $stubConstant = PhpStormStubsSingleton::getPhpStormStubs()->getClass($classId)->getConstant($constantName);
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
        $reflectedInterface = ReflectionStubsSingleton::getReflectionStubs()->getInterface($classId, fromReflection: true);
        $reflectionConstant = $reflectedInterface->getConstant($constantName, ConstantsFilterPredicateProvider::getConstantsFromReflection($constantName));
        $constantValue = $reflectionConstant->value;
        $stubConstant = PhpStormStubsSingleton::getPhpStormStubs()->getInterface($classId)->getConstant($constantName);
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
        $reflectionClass = ReflectionStubsSingleton::getReflectionStubs()->getClass($classId, sourceFilePath: true);
        $reflectionConstant = $reflectionClass->getConstant($constantName, ConstantsFilterPredicateProvider::getConstantsFromReflection($constantName));
        $constantVisibility = $reflectionConstant->visibility;
        $stubConstant = PhpStormStubsSingleton::getPhpStormStubs()->getClass($classId)->getConstant($constantName);
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
        $reflectionInterface = ReflectionStubsSingleton::getReflectionStubs()->getInterface($classId, fromReflection: true);
        $reflectionConstant = $reflectionInterface->getConstant($constantName, ConstantsFilterPredicateProvider::getConstantsFromReflection($constantName));
        $constantVisibility = $reflectionConstant->visibility;
        $stubConstant = PhpStormStubsSingleton::getPhpStormStubs()->getInterface($classId)->getConstant($constantName);
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
        $reflectionEnum = ReflectionStubsSingleton::getReflectionStubs()->getEnum($classId, fromReflection: true);
        $reflectionConstant = $reflectionEnum->getConstant($constantName, ConstantsFilterPredicateProvider::getConstantsFromReflection($constantName));
        $constantVisibility = $reflectionConstant->visibility;
        $stubConstant = PhpStormStubsSingleton::getPhpStormStubs()->getEnum($classId)->getConstant($constantName);
        static::assertEquals(
            $constantVisibility,
            $stubConstant->visibility,
            "Constant visibility mismatch: const $constantName \n
            Expected visibility: $constantVisibility but was $stubConstant->visibility"
        );
    }
}
