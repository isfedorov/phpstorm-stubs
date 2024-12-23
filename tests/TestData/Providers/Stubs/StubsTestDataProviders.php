<?php
declare(strict_types=1);

namespace StubTests\TestData\Providers\Stubs;

use DirectoryIterator;
use Generator;
use SplFileInfo;
use StubTests\Model\EntitiesProviders\EntitiesProvider;
use StubTests\Model\PHPFunction;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use function dirname;
use function in_array;

class StubsTestDataProviders
{
    public static function allFunctionsProvider(): ?Generator
    {
        foreach (EntitiesProvider::getFunctions(PhpStormStubsSingleton::getPhpStormStubs()) as $functionName => $function) {
            yield "function $functionName" => [$function->fqnBasedId];
        }
    }

    public static function allClassesProvider(): ?Generator
    {
        $classes = EntitiesProvider::getClasses(PhpStormStubsSingleton::getPhpStormStubs());
        foreach ($classes as $class) {
            yield "class {$class->getOrCreateStubSpecificProperties()->sourceFilePath}/$class->fqnBasedId" => [$class->getOrCreateStubSpecificProperties()->stubObjectHash];
        }
    }

    public static function allInterfacesProvider(): ?Generator
    {
        $interfaces = EntitiesProvider::getInterfaces(PhpStormStubsSingleton::getPhpStormStubs());
        foreach ($interfaces as $class) {
            yield "class {$class->getOrCreateStubSpecificProperties()->sourceFilePath}/$class->fqnBasedId" => [$class->getOrCreateStubSpecificProperties()->stubObjectHash];
        }
    }

    public static function allEnumsProvider(): ?Generator
    {
        $enums = EntitiesProvider::getEnums(PhpStormStubsSingleton::getPhpStormStubs());
        foreach ($enums as $class) {
            yield "class {$class->getOrCreateStubSpecificProperties()->sourceFilePath}/$class->fqnBasedId" => [$class->getOrCreateStubSpecificProperties()->stubObjectHash];
        }
    }

    public static function coreFunctionsProvider(): ?Generator
    {
        $allFunctions = EntitiesProvider::getFunctions(PhpStormStubsSingleton::getPhpStormStubs());
        $coreFunctions = array_filter($allFunctions, fn (PHPFunction $function): bool => $function->getOrCreateStubSpecificProperties()->stubBelongsToCore === true);
        foreach ($coreFunctions as $coreFunction) {
            yield "function $coreFunction->name" => [$coreFunction];
        }
    }

    public static function stubsDirectoriesProvider(): ?Generator
    {
        $stubsDirectory = dirname(__DIR__, 4);
        /** @var SplFileInfo $directory */
        foreach (new DirectoryIterator($stubsDirectory) as $directory) {
            $directoryName = $directory->getBasename();
            if ($directory->isDot() || !$directory->isDir() || in_array($directoryName, ['tests', 'meta', 'vendor'], true) || str_starts_with($directoryName, '.')) {
                continue;
            }
            yield "directory $directoryName" => [$directoryName];
        }
    }
}
