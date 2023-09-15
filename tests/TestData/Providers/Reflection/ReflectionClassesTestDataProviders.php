<?php
declare(strict_types=1);

namespace StubTests\TestData\Providers\Reflection;

use Generator;
use StubTests\Model\PHPClass;
use StubTests\Model\PHPEnum;
use StubTests\Model\PHPInterface;
use StubTests\Model\StubProblemType;
use StubTests\TestData\Providers\EntitiesFilter;
use StubTests\TestData\Providers\ReflectionStubsSingleton;

class ReflectionClassesTestDataProviders
{
    public static function allClassesProvider(): ?Generator
    {
        $allClassesAndInterfaces = ReflectionStubsSingleton::getReflectionStubs()->getClasses() +
            ReflectionStubsSingleton::getReflectionStubs()->getInterfaces() +
            ReflectionStubsSingleton::getReflectionStubs()->getEnums();
        /** @var \StubTests\Model\PHPNamespacedElement $class */
        foreach (EntitiesFilter::getFiltered($allClassesAndInterfaces) as $class) {
            //exclude classes from PHPReflectionParser
            if (strncmp($class->name, 'PHP', 3) !== 0) {
                yield "class {$class->namespace}\\$class->name" => [$class];
            }
        }
    }

    public static function classesWithInterfacesProvider(): ?Generator
    {
        foreach (EntitiesFilter::getFiltered(
            ReflectionStubsSingleton::getReflectionStubs()->getClasses() +
            ReflectionStubsSingleton::getReflectionStubs()->getEnums(),
            fn (PHPClass|PHPEnum $class) => empty($class->interfaces),
            StubProblemType::WRONG_INTERFACE
        ) as $class) {
            //exclude classes from PHPReflectionParser
            if (strncmp($class->name, 'PHP', 3) !== 0) {
                yield "class {$class->namespace}\\$class->name" => [$class];
            }
        }
    }

    public static function classWithParentProvider(): ?Generator
    {
        $classesAndInterfaces = ReflectionStubsSingleton::getReflectionStubs()->getClasses() +
            ReflectionStubsSingleton::getReflectionStubs()->getInterfaces() +
            ReflectionStubsSingleton::getReflectionStubs()->getEnums();
        $filtered = EntitiesFilter::getFiltered(
            $classesAndInterfaces,
            fn ($class) => empty($class->parentInterfaces) && empty($class->parentClass),
            StubProblemType::WRONG_PARENT
        );
        foreach ($filtered as $class) {
            yield "class {$class->namespace}\\$class->name" => [$class];
        }
    }

    public static function finalClassesProvider(): ?Generator
    {
        $classesAndInterfaces = ReflectionStubsSingleton::getReflectionStubs()->getClasses() +
            ReflectionStubsSingleton::getReflectionStubs()->getInterfaces() +
            ReflectionStubsSingleton::getReflectionStubs()->getEnums();
        $filtered = EntitiesFilter::getFiltered(
            $classesAndInterfaces,
            null,
            StubProblemType::WRONG_FINAL_MODIFIER
        );
        foreach ($filtered as $class) {
            yield "class {$class->namespace}\\$class->name" => [$class];
        }
    }

    public static function readonlyClassesProvider(): ?Generator
    {
        $classes = ReflectionStubsSingleton::getReflectionStubs()->getClasses();
        $filtered = EntitiesFilter::getFiltered(
            $classes,
            fn (PhpClass $class) => $class->isReadonly === false,
            StubProblemType::WRONG_READONLY
        );
        foreach ($filtered as $class) {
            yield "class $class->name" => [$class];
        }
    }

    public static function classWithNamespaceProvider(): ?Generator
    {
        $allClasses = ReflectionStubsSingleton::getReflectionStubs()->getClasses() +
            ReflectionStubsSingleton::getReflectionStubs()->getInterfaces() +
            ReflectionStubsSingleton::getReflectionStubs()->getEnums();
        $filtered = EntitiesFilter::getFiltered(
            $allClasses,
            fn (PhpClass|PHPInterface|PHPEnum $class) => empty($class->namespace)
        );
        foreach ($filtered as $class) {
            yield "class {$class->namespace}\\$class->name" => [$class];
        }
    }
}
