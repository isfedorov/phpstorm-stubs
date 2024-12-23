<?php
declare(strict_types=1);

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use StubTests\Model\EntitiesProviders\EntitiesProvider;
use StubTests\Model\PHPFunction;
use StubTests\Model\PHPParameter;
use StubTests\Model\Predicats\ClassesFilterPredicateProvider;
use StubTests\Model\Predicats\EnumsFilterPredicateProvider;
use StubTests\Model\Predicats\FunctionsFilterPredicateProvider;
use StubTests\Model\Predicats\InterfaceFilterPredicateProvider;
use StubTests\Model\StubProblemType;
use StubTests\Parsers\ParserUtils;
use StubTests\TestData\Providers\EntitiesFilter;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use StubTests\TestData\Providers\Reflection\ReflectionFunctionsProvider;
use StubTests\TestData\Providers\Reflection\ReflectionParametersProvider;
use StubTests\TestData\Providers\ReflectionStubsSingleton;

class BaseFunctionsTest extends AbstractBaseStubsTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        PhpStormStubsSingleton::getPhpStormStubs();
        ReflectionStubsSingleton::getReflectionStubs();
    }

    #[DataProviderExternal(ReflectionFunctionsProvider::class, 'allFunctionsProvider')]
    public function testFunctionsExist(?string $functionId): void
    {
        if (!$functionId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubFunction = EntitiesProvider::getFunction(PhpStormStubsSingleton::getPhpStormStubs(), FunctionsFilterPredicateProvider::getFunctionById($functionId));
        $reflectionFunction = EntitiesProvider::getFunction(ReflectionStubsSingleton::getReflectionStubs(), FUnctionsFilterPredicateProvider::getFunctionById($functionId));
        $params = AbstractBaseStubsTestCase::getParameterRepresentation($reflectionFunction);
        static::assertNotEmpty($stubFunction, "Missing function: function $reflectionFunction->fqnBasedId($params){}");
    }

    #[DataProviderExternal(ReflectionFunctionsProvider::class, 'functionsForDeprecationTestsProvider')]
    public function testFunctionsDeprecation(?string $functionId)
    {
        if (!$functionId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionFunction = EntitiesProvider::getFunction(ReflectionStubsSingleton::getReflectionStubs(), FUnctionsFilterPredicateProvider::getFunctionById($functionId));
        $stubFunction = EntitiesProvider::getFunction(PhpStormStubsSingleton::getPhpStormStubs(), FUnctionsFilterPredicateProvider::getFunctionById($functionId));
        static::assertEquals(
            $reflectionFunction->isDeprecated,
            $stubFunction->isDeprecated,
            "Deprecation of function $functionId is incorrect"
        );
    }

    #[DataProviderExternal(ReflectionFunctionsProvider::class, 'functionsForParamsAmountTestsProvider')]
    public function testFunctionsParametersAmount(?string $functionId)
    {
        if (!$functionId) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionFunction = EntitiesProvider::getFunction(ReflectionStubsSingleton::getReflectionStubs(), FunctionsFilterPredicateProvider::getFunctionById($functionId));
        $stubFunction = EntitiesProvider::getFunction(PhpStormStubsSingleton::getPhpStormStubs(), FunctionsFilterPredicateProvider::getFunctionById($functionId));
        $filteredStubParameters = array_filter(
            $stubFunction->parameters,
            fn ($parameter) => ParserUtils::entitySuitsCurrentPhpVersion($parameter)
        );
        $uniqueParameterNames = array_unique(array_map(fn (PHPParameter $parameter) => $parameter->name, $filteredStubParameters));

        static::assertSameSize(
            $reflectionFunction->parameters,
            $uniqueParameterNames,
            "Parameter number mismatch for function $functionId. 
                Expected: " . AbstractBaseStubsTestCase::getParameterRepresentation($reflectionFunction) . "\n" .
            'Actual: ' . AbstractBaseStubsTestCase::getParameterRepresentation($stubFunction)
        );
    }

    public function testFunctionsDuplicates()
    {
        $filtered = EntitiesFilter::getFiltered(
            EntitiesProvider::getFunctions(PhpStormStubsSingleton::getPhpStormStubs()),
            problemTypes: StubProblemType::HAS_DUPLICATION
        );
        $duplicates = self::getDuplicatedFunctions($filtered);
        self::assertCount(
            0,
            $duplicates,
            "Functions \"" . implode(', ', $duplicates) .
            "\" have duplicates in stubs.\nPlease use #[LanguageLevelTypeAware] or #[PhpStormStubsElementAvailable] if possible"
        );
    }

    #[DataProviderExternal(ReflectionParametersProvider::class, 'functionOptionalParametersProvider')]
    public function testFunctionsOptionalParameters(?string $functionId, ?string $parameterName)
    {
        if (!$functionId && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionFunction = EntitiesProvider::getFunction(ReflectionStubsSingleton::getReflectionStubs(), FunctionsFilterPredicateProvider::getFunctionById($functionId));
        $relfectionParameter = $reflectionFunction->getParameter($parameterName);
        $phpstormFunction = EntitiesProvider::getFunction(PhpStormStubsSingleton::getPhpStormStubs(), FunctionsFilterPredicateProvider::getFunctionById($functionId));
        $stubParameters = array_filter($phpstormFunction->parameters, fn (PHPParameter $stubParameter) => $stubParameter->indexInSignature === $relfectionParameter->indexInSignature);
        /** @var PHPParameter $stubOptionalParameter */
        $stubOptionalParameter = array_pop($stubParameters);
        self::assertEquals(
            $relfectionParameter->isOptional,
            $stubOptionalParameter->isOptional,
            sprintf(
                'Reflection function %s %s optional parameter %s with index %d 
            but stubs parameter %s with index %d %s',
                $reflectionFunction->name,
                $relfectionParameter->isOptional ? 'has' : 'has no',
                $relfectionParameter->name,
                $relfectionParameter->indexInSignature,
                $stubOptionalParameter->name,
                $stubOptionalParameter->indexInSignature,
                $stubOptionalParameter->isOptional ? 'is optional' : 'is not optional'
            )
        );
        self::assertEquals(
            $relfectionParameter->is_vararg,
            $stubOptionalParameter->is_vararg,
            sprintf(
                'Reflection function %s %s vararg parameter %s with index %d 
            but stubs parameter %s with index %d %s',
                $reflectionFunction->name,
                $relfectionParameter->is_vararg ? 'has' : 'has no',
                $relfectionParameter->name,
                $relfectionParameter->indexInSignature,
                $stubOptionalParameter->name,
                $stubOptionalParameter->indexInSignature,
                $stubOptionalParameter->is_vararg ? 'is vararg' : 'is not vararg'
            )
        );
    }

    #[DataProviderExternal(ReflectionParametersProvider::class, 'classMethodOptionalParametersProvider')]
    public function testClassMethodsOptionalParameters(?string $classId, ?string $methodName, ?string $parameterName)
    {
        if (!$classId && !$methodName && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionClass = EntitiesProvider::getClass(ReflectionStubsSingleton::getReflectionStubs(), ClassesFilterPredicateProvider::filterClassById($classId));
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $reflectionParameter = $reflectionMethod->getParameter($parameterName);
        $phpstormFunction = EntitiesProvider::getClass(PhpStormStubsSingleton::getPhpStormStubs(), ClassesFilterPredicateProvider::filterClassById($classId))->getMethod($methodName);
        $stubParameters = array_filter($phpstormFunction->parameters, fn (PHPParameter $stubParameter) => $stubParameter->indexInSignature === $reflectionParameter->indexInSignature);
        /** @var PHPParameter $stubOptionalParameter */
        $stubOptionalParameter = array_pop($stubParameters);
        self::assertEquals(
            $reflectionParameter->isOptional,
            $stubOptionalParameter->isOptional,
            sprintf(
                'Reflection method %s::%s has %s optional parameter %s with index %d but stub parameter %s with index %d is %s optional',
                $classId,
                $methodName,
                $reflectionParameter->isOptional ? "" : "not",
                $reflectionParameter->name,
                $reflectionParameter->indexInSignature,
                $stubOptionalParameter->name,
                $stubOptionalParameter->indexInSignature,
                $stubOptionalParameter->isOptional ? "" : "not"
            )
        );
    }

    #[DataProviderExternal(ReflectionParametersProvider::class, 'interfaceMethodOptionalParametersProvider')]
    public function testInterfaceMethodsOptionalParameters(?string $classId, ?string $methodName, ?string $parameterName)
    {
        if (!$classId && !$methodName && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionInterface = EntitiesProvider::getInterface(ReflectionStubsSingleton::getReflectionStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId));
        $reflectionMethod = $reflectionInterface->getMethod($methodName);
        $reflectionParameter = $reflectionMethod->getParameter($parameterName);
        $phpstormFunction = EntitiesProvider::getInterface(PhpStormStubsSingleton::getPhpStormStubs(), InterfaceFilterPredicateProvider::getInterfaceById($classId))->getMethod($methodName);
        $stubParameters = array_filter($phpstormFunction->parameters, fn (PHPParameter $stubParameter) => $stubParameter->indexInSignature === $reflectionParameter->indexInSignature);
        /** @var PHPParameter $stubOptionalParameter */
        $stubOptionalParameter = array_pop($stubParameters);
        self::assertEquals(
            $reflectionParameter->isOptional,
            $stubOptionalParameter->isOptional,
            sprintf(
                'Reflection method %s::%s has %s optional parameter %s with index %d but stub parameter %s with index %d is %s optional',
                $classId,
                $methodName,
                $reflectionParameter->isOptional ? "" : "not",
                $reflectionParameter->name,
                $reflectionParameter->indexInSignature,
                $stubOptionalParameter->name,
                $stubOptionalParameter->indexInSignature,
                $stubOptionalParameter->isOptional ? "" : "not"
            )
        );
    }

    #[DataProviderExternal(ReflectionParametersProvider::class, 'enumMethodOptionalParametersProvider')]
    public function testEnumMethodsOptionalParameters(?string $classId, ?string $methodName, ?string $parameterName)
    {
        if (!$classId && !$methodName && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $reflectionEnum = EntitiesProvider::getEnum(ReflectionStubsSingleton::getReflectionStubs(), EnumsFilterPredicateProvider::getEnumById($classId));
        $reflectionMethod = $reflectionEnum->getMethod($methodName);
        $reflectionParameter = $reflectionMethod->getParameter($parameterName);
        $phpstormFunction = EntitiesProvider::getEnum(PhpStormStubsSingleton::getPhpStormStubs(), EnumsFilterPredicateProvider::getEnumById($classId))->getMethod($methodName);
        $stubParameters = array_filter($phpstormFunction->parameters, fn (PHPParameter $stubParameter) => $stubParameter->indexInSignature === $reflectionParameter->indexInSignature);
        /** @var PHPParameter $stubOptionalParameter */
        $stubOptionalParameter = array_pop($stubParameters);
        self::assertEquals(
            $reflectionParameter->isOptional,
            $stubOptionalParameter->isOptional,
            sprintf(
                'Reflection method %s::%s has %s optional parameter %s with index %d but stub parameter %s with index %d is %s optional',
                $classId,
                $methodName,
                $reflectionParameter->isOptional ? "" : "not",
                $reflectionParameter->name,
                $reflectionParameter->indexInSignature,
                $stubOptionalParameter->name,
                $stubOptionalParameter->indexInSignature,
                $stubOptionalParameter->isOptional ? "" : "not"
            )
        );
    }

    public function testImplodeFunctionIsCorrect()
    {
        $implodeFunctions = array_filter(
            EntitiesProvider::getFunctions(PhpStormStubsSingleton::getPhpStormStubs()),
            fn (PHPFunction $function) => $function->fqnBasedId === '\implode'
        );
        self::assertCount(1, $implodeFunctions);
        /** @var PHPFunction $implodeFunction */
        $implodeFunction = array_pop($implodeFunctions);
        $implodeParameters = $implodeFunction->parameters;
        $separatorParameters = array_filter($implodeParameters, fn (PHPParameter $parameter) => $parameter->name === 'separator');
        $arrayParameters = array_filter($implodeParameters, fn (PHPParameter $parameter) => $parameter->name === 'array');
        /** @var PHPParameter $separatorParameter */
        $separatorParameter = array_pop($separatorParameters);
        /** @var PHPParameter $arrayParameter */
        $arrayParameter = array_pop($arrayParameters);
        self::assertCount(2, $implodeParameters);
        self::assertEquals(['array', 'string'], $separatorParameter->typesFromSignature);
        if (property_exists($separatorParameter->defaultValue, 'value')) {
            self::assertEquals('', $separatorParameter->defaultValue->value);
        } else {
            self::fail("Couldn't read default value");
        }
        self::assertEquals(['?array'], $arrayParameter->typesFromSignature);
        self::assertEquals(['string'], $implodeFunction->returnTypesFromSignature);
        self::assertEquals(['string'], $implodeFunction->returnTypesFromPhpDoc);
    }
}
