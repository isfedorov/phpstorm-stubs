<?php
declare(strict_types=1);

namespace StubTests;

use JetBrains\PhpStorm\Pure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use StubTests\Model\EntitiesProviders\EntitiesProvider;
use StubTests\Model\PHPParameter;
use StubTests\Model\Predicats\ClassesFilterPredicateProvider;
use StubTests\Model\Predicats\EnumsFilterPredicateProvider;
use StubTests\Model\Predicats\FunctionsFilterPredicateProvider;
use StubTests\Model\Predicats\InterfaceFilterPredicateProvider;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use StubTests\TestData\Providers\Reflection\ReflectionParametersProvider;
use StubTests\TestData\Providers\ReflectionStubsSingleton;

class StubsParameterNamesTest extends AbstractBaseStubsTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        PhpStormStubsSingleton::getPhpStormStubs();
        ReflectionStubsSingleton::getReflectionStubs();
    }

    #[DataProviderExternal(ReflectionParametersProvider::class, 'functionParametersProvider')]
    public function testFunctionsParameterNames(?string $functionId, ?string $parameterName)
    {
        if (!$functionId && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionFunction = EntitiesProvider::getFunction(ReflectionStubsSingleton::getReflectionStubs(), FunctionsFilterPredicateProvider::getFunctionById($functionId));
        $phpstormFunction = EntitiesProvider::getFunction(PhpStormStubsSingleton::getPhpStormStubs(),FunctionsFilterPredicateProvider::getFunctionById($functionId));
        self::assertNotEmpty(
            array_filter(
                $phpstormFunction->parameters,
                fn (PHPParameter $stubParameter) => $stubParameter->name === $parameterName
            ),
            "Function $functionId has signature $functionId(" . self::printParameters($reflectionFunction->parameters) . ')' .
            " but stub function has signature $phpstormFunction->name(" . self::printParameters($phpstormFunction->parameters) . ')'
        );
    }

    #[DataProviderExternal(ReflectionParametersProvider::class, 'classMethodsParametersProvider')]
    public function testClassMethodsParameterNames(?string $classId, ?string $methodName, ?string $parameterName)
    {
        if (!$classId && !$methodName && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $reflectionMethod = $reflectionClass->getMethod($methodName, FunctionsFilterPredicateProvider::getMethodsFromReflection($methodName));
        $reflectionParameter = $reflectionMethod->getParameter($parameterName);
        $stubMethod = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId))->getMethod($methodName);
        self::assertNotEmpty(
            array_filter(
                $stubMethod->parameters,
                fn (PHPParameter $stubParameter) => $stubParameter->name === $reflectionParameter->name
            ),
            "Method $classId::$methodName has signature $methodName(" . self::printParameters($reflectionMethod->parameters) . ')' .
            " but stub function has signature $methodName(" . self::printParameters($stubMethod->parameters) . ')'
        );
    }

    #[DataProviderExternal(ReflectionParametersProvider::class, 'interfaceMethodsParametersProvider')]
    public function testInterfaceMethodsParameterNames(?string $classId, ?string $methodName, ?string $parameterName)
    {
        if (!$classId && !$methodName && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionInterface = EntitiesProvider::getInterface(ReflectionStubsSingleton::getReflectionStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        $reflectionMethod = $reflectionInterface->getMethod($methodName, FunctionsFilterPredicateProvider::getMethodsFromReflection($methodName));
        $reflectionParameter = $reflectionMethod->getParameter($parameterName);
        $stubMethod = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId))->getMethod($methodName);
        self::assertNotEmpty(
            array_filter(
                $stubMethod->parameters,
                fn (PHPParameter $stubParameter) => $stubParameter->name === $reflectionParameter->name
            ),
            "Method $classId::$methodName has signature $methodName(" . self::printParameters($reflectionMethod->parameters) . ')' .
            " but stub function has signature $methodName(" . self::printParameters($stubMethod->parameters) . ')'
        );
    }

    #[DataProviderExternal(ReflectionParametersProvider::class, 'enumMethodsParametersProvider')]
    public function testEnumMethodsParameterNames(?string $classId, ?string $methodName, ?string $parameterName)
    {
        if (!$classId && !$methodName && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionEnum = EntitiesProvider::getEnum(ReflectionStubsSingleton::getReflectionStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        $reflectionMethod = $reflectionEnum->getMethod($methodName, FunctionsFilterPredicateProvider::getMethodsFromReflection($methodName));
        $reflectionParameter = $reflectionMethod->getParameter($parameterName);
        $stubMethod = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($classId))->getMethod($methodName);
        self::assertNotEmpty(
            array_filter(
                $stubMethod->parameters,
                fn (PHPParameter $stubParameter) => $stubParameter->name === $reflectionParameter->name
            ),
            "Method $classId::$methodName has signature $methodName(" . self::printParameters($reflectionMethod->parameters) . ')' .
            " but stub function has signature $methodName(" . self::printParameters($stubMethod->parameters) . ')'
        );
    }

    /**
     * @param PHPParameter[] $params
     */
    #[Pure]
    public static function printParameters(array $params): string
    {
        $signature = '';
        foreach ($params as $param) {
            $signature .= '$' . $param->name . ', ';
        }
        return trim($signature, ', ');
    }
}
