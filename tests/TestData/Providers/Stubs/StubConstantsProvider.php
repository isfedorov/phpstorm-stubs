<?php
declare(strict_types=1);

namespace StubTests\TestData\Providers\Stubs;

use Generator;
use StubTests\Model\EntitiesProviders\EntitiesProvider;
use StubTests\Model\PHPEnum;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use StubTests\TestData\Providers\ReflectionStubsSingleton;

class StubConstantsProvider
{
    public static function classConstantProvider(): ?Generator
    {
        $classes = EntitiesProvider::getClasses(PhpStormStubsSingleton::getPhpStormStubs());
        if (empty($classes)) {
            yield [null, null];
        }else {
            foreach ($classes as $class) {
                foreach ($class->constants as $constant) {
                    yield "constant $class->fqnBasedId::$constant->name [{$class->getOrCreateStubSpecificProperties()->stubObjectHash}]" => [$class->getOrCreateStubSpecificProperties()->stubObjectHash, $constant->name];
                }
            }
        }
    }

    public static function interfaceConstantProvider(): ?Generator
    {
        $interfaces = EntitiesProvider::getInterfaces(PhpStormStubsSingleton::getPhpStormStubs());
        if (empty($interfaces)) {
            yield [null, null];
        }else {
            foreach ($interfaces as $class) {
                foreach ($class->constants as $constant) {
                    yield "constant $class->fqnBasedId::$constant->name" => [$class->fqnBasedId, $constant->name];
                }
            }
        }
    }

    public static function enumConstantProvider(): ?Generator
    {
        $enums = EntitiesProvider::getEnums(PhpStormStubsSingleton::getPhpStormStubs());
        $constants = array_filter(array_map(fn (PHPEnum $enum) => $enum->constants, $enums), fn ($constants) => !empty($constants));
        if (empty($constants)) {
            yield [null, null];
        }else {
            foreach ($enums as $class) {
                foreach ($class->constants as $constant) {
                    yield "constant $class->fqnBasedId::$constant->name" => [$class->fqnBasedId, $constant->name];
                }
            }
        }
    }

    public static function globalConstantProvider(): ?Generator
    {
        foreach (EntitiesProvider::getConstants(PhpStormStubsSingleton::getPhpStormStubs()) as $constantId => $constant) {
            yield "constant $constantId" => [$constantId];
        }
    }
}
