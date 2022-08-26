<?php
declare(strict_types=1);

namespace StubTests\TestData\Providers\Reflection;

use Generator;
use StubTests\Model\PHPProperty;
use StubTests\Model\StubProblemType;
use StubTests\TestData\Providers\EntitiesFilter;
use StubTests\TestData\Providers\ReflectionStubsSingleton;

class ReflectionPropertiesProvider
{
    public static function classPropertiesProvider(): Generator
    {
        return self::yieldFilteredMethodProperties(null);
    }

    public static function classStaticPropertiesProvider(): Generator
    {
        return self::yieldFilteredMethodProperties(
            fn (PHPProperty $property) => $property->isStatic === false,
            StubProblemType::PROPERTY_IS_STATIC
        );
    }

    public static function classPropertiesWithAccessProvider(): Generator
    {
        return self::yieldFilteredMethodProperties(
            fn (PHPProperty $property) => empty($property->access),
            StubProblemType::PROPERTY_ACCESS
        );
    }

    public static function classPropertiesWithTypeProvider(): Generator
    {
        return self::yieldFilteredMethodProperties(null, StubProblemType::PROPERTY_TYPE);
    }

    public static function classReadonlyPropertiesProvider(): Generator
    {
        return self::yieldFilteredMethodProperties(
            fn (PHPProperty $property) => $property->isReadonly === true,
            StubProblemType::WRONG_READONLY
        );
    }

    private static function yieldFilteredMethodProperties(?callable $additionalFilter, int ...$problemTypes): ?Generator
    {
        $classesAndInterfaces = ReflectionStubsSingleton::getReflectionStubs()->getClasses();
        foreach (EntitiesFilter::getFiltered($classesAndInterfaces) as $class) {
            foreach (EntitiesFilter::getFiltered(
                $class->properties,
                $additionalFilter,
                ...$problemTypes
            ) as $property) {
                yield "Property $class->name::$property->name" => [$class, $property];
            }
        }
    }
}
