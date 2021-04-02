<?php
declare(strict_types=1);

namespace StubTests\TestData\Providers\Stubs;

use Generator;
use LogicException;
use RuntimeException;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use UnexpectedValueException;

class StubsTestDataProviders
{
    /**
     * @throws UnexpectedValueException|LogicException|RuntimeException
     */
    public static function allFunctionsProvider(): ?Generator
    {
        foreach (PhpStormStubsSingleton::getPhpStormStubs()->getFunctions() as $functionName => $function) {
            yield "function $functionName" => [$function];
        }
    }

    /**
     * @throws LogicException|UnexpectedValueException|RuntimeException
     */
    public static function allClassesProvider(): ?Generator
    {
        $allClassesAndInterfaces = PhpStormStubsSingleton::getPhpStormStubs()->getClasses() +
            PhpStormStubsSingleton::getPhpStormStubs()->getInterfaces();
        foreach ($allClassesAndInterfaces as $class) {
            yield "class $class->sourceFilePath/$class->name" => [$class];
        }
    }
}
