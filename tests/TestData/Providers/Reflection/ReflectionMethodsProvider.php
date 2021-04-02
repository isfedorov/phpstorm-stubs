<?php
declare(strict_types=1);

namespace StubTests\TestData\Providers\Reflection;

use Generator;
use ReflectionException;
use StubTests\Model\StubProblemType;
use StubTests\TestData\Providers\EntitiesFilter;
use StubTests\TestData\Providers\ReflectionStubsSingleton;

class ReflectionMethodsProvider
{
    /**
     * @throws ReflectionException
     */
    public static function classMethodsProvider(): ?Generator
    {
        return self::yieldFilteredMethods();
    }

    /**
     * @throws ReflectionException
     */
    public static function classMethodsWithReturnTypeHintProvider(): ?Generator
    {
        return self::yieldFilteredMethods(StubProblemType::WRONG_RETURN_TYPEHINT);
    }

    /**
     * @throws ReflectionException
     */
    public static function classMethodsWithAccessProvider(): ?Generator
    {
        return self::yieldFilteredMethods(StubProblemType::FUNCTION_ACCESS);
    }

    /**
     * @throws ReflectionException
     */
    public static function classFinalMethodsProvider(): ?Generator
    {
        return self::yieldFilteredMethods(StubProblemType::FUNCTION_IS_FINAL);
    }

    /**
     * @throws ReflectionException
     */
    public static function classStaticMethodsProvider(): ?Generator
    {
        return self::yieldFilteredMethods(StubProblemType::FUNCTION_IS_STATIC);
    }

    /**
     * @throws ReflectionException
     */
    public static function classMethodsWithParametersProvider(): ?Generator
    {
        return self::yieldFilteredMethods(StubProblemType::FUNCTION_PARAMETER_MISMATCH);
    }

    /**
     * @throws ReflectionException
     */
    private static function yieldFilteredMethods(int ...$problemTypes): ?Generator
    {
        $classesAndInterfaces = ReflectionStubsSingleton::getReflectionStubs()->getClasses() +
            ReflectionStubsSingleton::getReflectionStubs()->getInterfaces();
        foreach (EntitiesFilter::getFiltered($classesAndInterfaces) as $class) {
            foreach (EntitiesFilter::getFiltered($class->methods, null, ...$problemTypes) as $method) {
                yield "Method $class->name::$method->name" => [$class, $method];
            }
        }
    }
}
