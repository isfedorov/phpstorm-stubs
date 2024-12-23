<?php
declare(strict_types=1);

namespace StubTests\TestData\Providers\Reflection;

use Generator;
use StubTests\Model\EntitiesProviders\EntitiesProvider;
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
        $classes = EntitiesProvider::getClasses(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered($classes, fn (PHPClass $class) => $class->name === "DOMException");
        if (empty($filtered)) {
            yield [null];
        }else {
            foreach ($filtered as $class) {
                //exclude classes from PHPReflectionParser
                if (strncmp($class->name, 'PHP', 3) !== 0) {
                    yield "class $class->fqnBasedId" => [$class->fqnBasedId];
                }
            }
        }
    }

    public static function allInterfacesProvider(): ?Generator
    {
        $interfaces = EntitiesProvider::getInterfaces(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered($interfaces);
        if (empty($filtered)) {
            yield [null];
        } else {
            foreach ($filtered as $interface) {
                yield "interface $interface->fqnBasedId" => [$interface->fqnBasedId];
            }
        }
    }

    public static function allEnumsProvider(): ?Generator
    {
        $enums = EntitiesProvider::getEnums(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered($enums);
        if (empty($filtered)) {
            yield [null];
        }else {
            foreach ($filtered as $enum) {
                yield "enum $enum->fqnBasedId" => [$enum->fqnBasedId];
            }
        }
    }

    public static function classesWithInterfacesProvider(): ?Generator
    {
        $filtered = EntitiesFilter::getFiltered(
            EntitiesProvider::getClasses(ReflectionStubsSingleton::getReflectionStubs()),
            fn (PHPClass $class) => empty($class->interfaces) || $class->name == 'DOMException',
            StubProblemType::WRONG_INTERFACE
        );
        if (empty($filtered)) {
            yield [null];
        } else {
            foreach ($filtered as $class) {
                //exclude classes from PHPReflectionParser
                if (strncmp($class->name, 'PHP', 3) !== 0) {
                    yield "class $class->fqnBasedId" => [$class->fqnBasedId];
                }
            }
        }
    }

    public static function enumsWithInterfacesProvider(): ?Generator
    {
        $filtered = EntitiesFilter::getFiltered(
            EntitiesProvider::getEnums(ReflectionStubsSingleton::getReflectionStubs()),
            fn (PHPEnum $enum) => empty($enum->interfaces),
            StubProblemType::WRONG_INTERFACE
        );
        if (empty($filtered)) {
            yield [null];
        }else {
            foreach ($filtered as $enum) {
                yield "enum $enum->fqnBasedId" => [$enum->fqnBasedId];
            }
        }
    }

    public static function classWithParentProvider(): ?Generator
    {
        $classes = EntitiesProvider::getClasses(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered(
            $classes,
            fn (PHPClass $class) => empty($class->parentClass) || $class->name == 'DOMException',
            StubProblemType::WRONG_PARENT
        );
        if (empty($filtered)) {
            yield [null];
        } else {
            foreach ($filtered as $class) {
                yield "class $class->fqnBasedId" => [$class->fqnBasedId];
            }
        }
    }

    public static function interfaceWithParentProvider(): ?Generator
    {
        $interfaces = EntitiesProvider::getInterfaces(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered(
            $interfaces,
            fn (PHPInterface $interface) => empty($interface->parentInterfaces),
            StubProblemType::WRONG_PARENT
        );
        if (empty($filtered)) {
            yield [null];
        } else {
            foreach ($filtered as $interface) {
                yield "interface $interface->fqnBasedId" => [$interface->fqnBasedId];
            }
        }
    }

    public static function enumWithParentProvider(): ?Generator
    {
        $enums = EntitiesProvider::getEnums(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered($enums, fn (PHPEnum $enum) => empty($enum->parentClass), StubProblemType::WRONG_PARENT);
        if (empty($filtered)) {
            yield [null];
        } else {
            foreach ($filtered as $enum) {
                yield "enum $enum->fqnBasedId" => [$enum->fqnBasedId];
            }
        }
    }

    public static function finalClassesProvider(): ?Generator
    {
        $classes = EntitiesProvider::getClasses(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered(
            $classes,
            fn (PHPClass $class) => $class->name === "DOMException" || $class->name === '__PHP_Incomplete_Class',
            StubProblemType::WRONG_FINAL_MODIFIER
        );
        if (empty($filtered)) {
            yield [null];
        }else {
            foreach ($filtered as $class) {
                yield "class $class->fqnBasedId" => [$class->fqnBasedId];
            }
        }
    }

    public static function finalInterfacesProvider(): ?Generator
    {
        $interfaces = EntitiesProvider::getInterfaces(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered($interfaces, null, StubProblemType::WRONG_FINAL_MODIFIER);
        if (empty($filtered)) {
            yield [null];
        } else {
            foreach ($filtered as $class) {
                yield "interface $class->fqnBasedId" => [$class->fqnBasedId];
            }
        }
    }

    public static function finalEnumsProvider(): ?Generator
    {
        $enums = EntitiesProvider::getEnums(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered($enums, null, StubProblemType::WRONG_FINAL_MODIFIER);
        if (empty($filtered)) {
            yield [null];
        } else {
            foreach ($filtered as $class) {
                yield "enum $class->fqnBasedId" => [$class->fqnBasedId];
            }
        }
    }

    public static function readonlyClassesProvider(): ?Generator
    {
        $classes = EntitiesProvider::getClasses(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered($classes, fn (PHPClass $class) => $class->name === "DOMException", StubProblemType::WRONG_READONLY);
        if (empty($filtered)) {
            yield [null];
        } else {
            foreach ($filtered as $class) {
                yield "class $class->fqnBasedId" => [$class->fqnBasedId];
            }
        }
    }

    public static function classWithNamespaceProvider(): ?Generator
    {
        $interfaces = EntitiesProvider::getClasses(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered($interfaces, fn (PHPClass $class) => empty($class->namespace) || $class->name == 'DOMException');
        if (empty($filtered)) {
            yield [null];
        }else {
            foreach ($filtered as $class) {
                yield "class $class->fqnBasedId" => [$class->fqnBasedId];
            }
        }
    }

    public static function interfaceWithNamespaceProvider(): ?Generator
    {
        $interfaces = EntitiesProvider::getInterfaces(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered($interfaces, fn (PHPInterface $class) => empty($class->namespace));
        if (empty($filtered)) {
            yield [null];
        }else {
            foreach ($filtered as $class) {
                yield "interface $class->fqnBasedId" => [$class->fqnBasedId];
            }
        }
    }

    public static function enumsWithNamespaceProvider(): ?Generator
    {
        $enums = EntitiesProvider::getEnums(ReflectionStubsSingleton::getReflectionStubs());
        $filtered = EntitiesFilter::getFiltered($enums, fn (PHPEnum $class) => empty($class->namespace));
        if (empty($filtered)) {
            yield [null];
        }else {
            foreach ($filtered as $class) {
                yield "enum $class->fqnBasedId" => [$class->fqnBasedId];
            }
        }
    }
}
