<?php
declare(strict_types=1);

namespace StubTests\TestData\Providers\Reflection;

use Generator;
use ReflectionException;
use StubTests\Model\PHPProperty;
use StubTests\Model\StubProblemType;
use StubTests\TestData\Providers\EntitiesFilter;
use StubTests\TestData\Providers\ReflectionStubsSingleton;

class ReflectionPropertiesProvider
{
    /**
     * @throws ReflectionException
     */
    public static function classPropertiesProvider(): Generator
    {
        return self::yieldFilteredMethods();
    }

    /**
     * @throws ReflectionException
     */
    public static function classStaticPropertiesProvider(): Generator
    {
        return self::yieldFilteredMethods(StubProblemType::PROPERTY_IS_STATIC);
    }

    /**
     * @throws ReflectionException
     */
    public static function classPropertiesWithAccessProvider(): Generator
    {
        return self::yieldFilteredMethods(StubProblemType::PROPERTY_ACCESS);
    }

    /**
     * @throws ReflectionException
     */
    public static function classPropertiesWithTypeProvider(): Generator
    {
        return self::yieldFilteredMethods(StubProblemType::PROPERTY_TYPE);
    }

    /**
     * @throws ReflectionException
     */
    private static function yieldFilteredMethods(int ...$problemTypes): ?Generator
    {
        $classesAndInterfaces = ReflectionStubsSingleton::getReflectionStubs()->getClasses();
        foreach (EntitiesFilter::getFiltered($classesAndInterfaces) as $class) {
            foreach (EntitiesFilter::getFiltered($class->properties,
                fn (PHPProperty $property) => $property->access === 'private', ...$problemTypes) as $property) {
                yield "Property $class->name::$property->name" => [$class, $property];
            }
        }
    }
}
