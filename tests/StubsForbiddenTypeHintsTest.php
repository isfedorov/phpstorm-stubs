<?php

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use StubTests\Model\Predicats\FunctionsFilterPredicateProvider;
use StubTests\Parsers\ParserUtils;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use StubTests\TestData\Providers\ReflectionStubsSingleton;
use StubTests\TestData\Providers\Stubs\StubMethodsProvider;
use StubTests\TestData\Providers\Stubs\StubsParametersProvider;

class StubsForbiddenTypeHintsTest extends AbstractBaseStubsTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        PhpStormStubsSingleton::getPhpStormStubs();
        ReflectionStubsSingleton::getReflectionStubs();
    }

    #[DataProviderExternal(StubMethodsProvider::class, 'classMethodsForNullableReturnTypeHintTestsProvider')]
    public function testClassMethodDoesNotHaveNullableReturnTypeHint(?string $classHash, ?string $methodHash)
    {
        if (!$classHash && !$methodHash) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubClass = PhpStormStubsSingleton::getPhpStormStubs()->getClassByHash($classHash);
        $stubMethod = $stubClass->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubMethod);
        $returnTypes = $stubMethod->returnTypesFromSignature;
        self::assertEmpty(
            array_filter($returnTypes, fn (string $type) => str_contains($type, '?')),
            "Method '$stubMethod->parentId::$stubMethod->name' has since version '$sinceVersion'
            but has nullable return typehint '" . implode('|', $returnTypes) . "' that supported only since PHP 7.1. 
            Please declare return type via PhpDoc"
        );
    }

    #[DataProviderExternal(StubMethodsProvider::class, 'interfaceMethodsForNullableReturnTypeHintTestsProvider')]
    public function testInterfaceMethodDoesNotHaveNullableReturnTypeHint(?string $classHash, ?string $methodHash)
    {
        if (!$classHash && !$methodHash) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubInterface = PhpStormStubsSingleton::getPhpStormStubs()->getInterfaceByHash($classHash);
        $stubMethod = $stubInterface->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubMethod);
        $returnTypes = $stubMethod->returnTypesFromSignature;
        self::assertEmpty(
            array_filter($returnTypes, fn (string $type) => str_contains($type, '?')),
            "Method '$stubMethod->parentId::$stubMethod->name' has since version '$sinceVersion'
            but has nullable return typehint '" . implode('|', $returnTypes) . "' that supported only since PHP 7.1. 
            Please declare return type via PhpDoc"
        );
    }

    #[DataProviderExternal(StubMethodsProvider::class, 'enumMethodsForNullableReturnTypeHintTestsProvider')]
    public function testEnumMethodDoesNotHaveNullableReturnTypeHint(?string $classHash, ?string $methodHash)
    {
        if (!$classHash && !$methodHash) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubEnum = PhpStormStubsSingleton::getPhpStormStubs()->getEnumByHash($classHash);
        $stubMethod = $stubEnum->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubMethod);
        $returnTypes = $stubMethod->returnTypesFromSignature;
        self::assertEmpty(
            array_filter($returnTypes, fn (string $type) => str_contains($type, '?')),
            "Method '$stubMethod->parentId::$stubMethod->name' has since version '$sinceVersion'
            but has nullable return typehint '" . implode('|', $returnTypes) . "' that supported only since PHP 7.1. 
            Please declare return type via PhpDoc"
        );
    }

    #[DataProviderExternal(StubsParametersProvider::class, 'classMethodsParametersForUnionTypeHintTestsProvider')]
    public function testClassMethodDoesNotHaveUnionTypeHintsInParameters(?string $classHash, ?string $methodHash, ?string $parameterName)
    {
        if (!$classHash && !$methodHash && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubsClass = PhpStormStubsSingleton::getPhpStormStubs()->getClassByHash($classHash);
        $stubsMethod = $stubsClass->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $stubParameter = $stubsMethod->getParameter($parameterName);
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubsMethod);
        self::assertLessThan(
            2,
            count($stubParameter->typesFromSignature),
            "Method '$stubsClass->fqnBasedId::$stubsMethod->name' with @since '$sinceVersion'  
                has parameter '$parameterName' with union typehint '" . implode('|', $stubParameter->typesFromSignature) . "' 
                but union typehints available only since php 8.0"
        );
    }

    #[DataProviderExternal(StubsParametersProvider::class, 'interfaceMethodsParametersForUnionTypeHintTestsProvider')]
    public function testInterfaceMethodDoesNotHaveUnionTypeHintsInParameters(?string $classHash, ?string $methodHash, ?string $parameterName)
    {
        if (!$classHash && !$methodHash && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubsInterface = PhpStormStubsSingleton::getPhpStormStubs()->getInterfaceByHash($classHash);
        $stubsMethod = $stubsInterface->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $stubParameter = $stubsMethod->getParameter($parameterName);
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubsMethod);
        self::assertLessThan(
            2,
            count($stubParameter->typesFromSignature),
            "Method '$stubsInterface->fqnBasedId::$stubsMethod->name' with @since '$sinceVersion'  
                has parameter '$parameterName' with union typehint '" . implode('|', $stubParameter->typesFromSignature) . "' 
                but union typehints available only since php 8.0"
        );
    }

    #[DataProviderExternal(StubsParametersProvider::class, 'enumMethodsParametersForUnionTypeHintTestsProvider')]
    public function testEnumMethodDoesNotHaveUnionTypeHintsInParameters(?string $classHash, ?string $methodHash, ?string $parameterName)
    {
        if (!$classHash && !$methodHash && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubsEnum = PhpStormStubsSingleton::getPhpStormStubs()->getEnumByHash($classHash);
        $stubsMethod = $stubsEnum->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $stubParameter = $stubsMethod->getParameter($parameterName);
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubsMethod);
        self::assertLessThan(
            2,
            count($stubParameter->typesFromSignature),
            "Method '$stubsEnum->fqnBasedId::$stubsMethod->name' with @since '$sinceVersion'  
                has parameter '$parameterName' with union typehint '" . implode('|', $stubParameter->typesFromSignature) . "' 
                but union typehints available only since php 8.0"
        );
    }

    #[DataProviderExternal(StubsParametersProvider::class, 'classMethodsParametersForNullableTypeHintTestsProvider')]
    public function testClassMethodDoesNotHaveNullableTypeHintsInParameters(?string $classHash, ?string $methodHash, ?string $parameterName)
    {
        if (!$classHash && !$methodHash && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubsClass = PhpStormStubsSingleton::getPhpStormStubs()->getClassByHash($classHash);
        $stubsMethod = $stubsClass->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $stubParameter = $stubsMethod->getParameter($parameterName);
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubsMethod);
        self::assertEmpty(
            array_filter($stubParameter->typesFromSignature, fn (string $type) => str_contains($type, '?')),
            "Method '$stubsClass->fqnBasedId::$stubsMethod->name' with @since '$sinceVersion'  
                has nullable parameter '$parameterName' with typehint '" . implode('|', $stubParameter->typesFromSignature) . "' 
                but nullable typehints available only since php 7.1"
        );
    }

    #[DataProviderExternal(StubsParametersProvider::class, 'interfaceMethodsParametersForNullableTypeHintTestsProvider')]
    public function testInterfaceMethodDoesNotHaveNullableTypeHintsInParameters(?string $classHash, ?string $methodHash, ?string $parameterName)
    {
        if (!$classHash && !$methodHash && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubsInterface = PhpStormStubsSingleton::getPhpStormStubs()->getInterfaceByHash($classHash);
        $stubsMethod = $stubsInterface->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $stubParameter = $stubsMethod->getParameter($parameterName);
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubsMethod);
        self::assertEmpty(
            array_filter($stubParameter->typesFromSignature, fn (string $type) => str_contains($type, '?')),
            "Method '$stubsInterface->fqnBasedId::$stubsMethod->name' with @since '$sinceVersion'  
                has nullable parameter '$parameterName' with typehint '" . implode('|', $stubParameter->typesFromSignature) . "' 
                but nullable typehints available only since php 7.1"
        );
    }

    #[DataProviderExternal(StubsParametersProvider::class, 'enumMethodsParametersForNullableTypeHintTestsProvider')]
    public function testEnumMethodDoesNotHaveNullableTypeHintsInParameters(?string $classHash, ?string $methodHash, ?string $parameterName)
    {
        if (!$classHash && !$methodHash && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubsEnum = PhpStormStubsSingleton::getPhpStormStubs()->getEnumByHash($classHash);
        $stubsMethod = $stubsEnum->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $stubParameter = $stubsMethod->getParameter($parameterName);
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubsMethod);
        self::assertEmpty(
            array_filter($stubParameter->typesFromSignature, fn (string $type) => str_contains($type, '?')),
            "Method '$stubsEnum->fqnBasedId::$stubsMethod->name' with @since '$sinceVersion'  
                has nullable parameter '$parameterName' with typehint '" . implode('|', $stubParameter->typesFromSignature) . "' 
                but nullable typehints available only since php 7.1"
        );
    }

    #[DataProviderExternal(StubMethodsProvider::class, 'classMethodsForUnionReturnTypeHintTestsProvider')]
    public function testClassMethodDoesNotHaveUnionReturnTypeHint(?string $classHash, ?string $methodHash)
    {
        if (!$classHash && !$methodHash) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubClass = PhpStormStubsSingleton::getPhpStormStubs()->getClassByHash($classHash);
        $stubsMethod = $stubClass->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubsMethod);
        self::assertLessThan(
            2,
            count($stubsMethod->returnTypesFromSignature),
            "Method '$stubsMethod->parentId::$stubsMethod->name' has since version '$sinceVersion'
            but has union return typehint '" . implode('|', $stubsMethod->returnTypesFromSignature) . "' that supported only since PHP 8.0. 
            Please declare return type via PhpDoc"
        );
    }

    #[DataProviderExternal(StubMethodsProvider::class, 'interfaceMethodsForUnionReturnTypeHintTestsProvider')]
    public function testInterfaceMethodDoesNotHaveUnionReturnTypeHint(?string $classHash, ?string $methodHash)
    {
        if (!$classHash && !$methodHash) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubInterface = PhpStormStubsSingleton::getPhpStormStubs()->getInterfaceByHash($classHash);
        $stubsMethod = $stubInterface->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubsMethod);
        self::assertLessThan(
            2,
            count($stubsMethod->returnTypesFromSignature),
            "Method '$stubsMethod->parentId::$stubsMethod->name' has since version '$sinceVersion'
            but has union return typehint '" . implode('|', $stubsMethod->returnTypesFromSignature) . "' that supported only since PHP 8.0. 
            Please declare return type via PhpDoc"
        );
    }

    #[DataProviderExternal(StubMethodsProvider::class, 'enumMethodsForUnionReturnTypeHintTestsProvider')]
    public function testEnumMethodDoesNotHaveUnionReturnTypeHint(?string $classHash, ?string $methodHash)
    {
        if (!$classHash && !$methodHash) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubEnum = PhpStormStubsSingleton::getPhpStormStubs()->getEnumByHash($classHash);
        $stubsMethod = $stubEnum->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubsMethod);
        self::assertLessThan(
            2,
            count($stubsMethod->returnTypesFromSignature),
            "Method '$stubsMethod->parentId::$stubsMethod->name' has since version '$sinceVersion'
            but has union return typehint '" . implode('|', $stubsMethod->returnTypesFromSignature) . "' that supported only since PHP 8.0. 
            Please declare return type via PhpDoc"
        );
    }

    #[DataProviderExternal(StubsParametersProvider::class, 'classMethodsParametersForScalarTypeHintTestsProvider')]
    public function testClassMethodDoesNotHaveScalarTypeHintsInParameters(?string $classHash, ?string $methodHash, ?string $parameterName)
    {
        if (!$classHash && !$methodHash && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $class = PhpStormStubsSingleton::getPhpStormStubs()->getClassByHash($classHash);
        $stubsMethod = $class->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $stubsParameter = $stubsMethod->getParameter($parameterName);
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubsMethod);
        self::assertEmpty(
            array_intersect(['int', 'float', 'string', 'bool', 'mixed', 'object'], $stubsParameter->typesFromSignature),
            "Method '$class->fqnBasedId::$stubsMethod->name' with @since '$sinceVersion'  
                has parameter '$parameterName' with typehint '" . implode('|', $stubsParameter->typesFromSignature) .
            "' but typehints available only since php 7"
        );
    }

    #[DataProviderExternal(StubsParametersProvider::class, 'interfaceMethodsParametersForScalarTypeHintTestsProvider')]
    public function testInterfaceMethodDoesNotHaveScalarTypeHintsInParameters(?string $classHash, ?string $methodHash, ?string $parameterName)
    {
        if (!$classHash && !$methodHash && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubsIntefrace = PhpStormStubsSingleton::getPhpStormStubs()->getInterfaceByHash($classHash);
        $stubsMethod = $stubsIntefrace->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $stubsParameter = $stubsMethod->getParameter($parameterName);
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubsMethod);
        self::assertEmpty(
            array_intersect(['int', 'float', 'string', 'bool', 'mixed', 'object'], $stubsParameter->typesFromSignature),
            "Method '$stubsIntefrace->fqnBasedId::$stubsMethod->name' with @since '$sinceVersion'  
                has parameter '$parameterName' with typehint '" . implode('|', $stubsParameter->typesFromSignature) .
            "' but typehints available only since php 7"
        );
    }

    #[DataProviderExternal(StubsParametersProvider::class, 'enumMethodsParametersForScalarTypeHintTestsProvider')]
    public function testEnumMethodDoesNotHaveScalarTypeHintsInParameters(?string $classHash, ?string $methodHash, ?string $parameterName)
    {
        if (!$classHash && !$methodHash && !$parameterName) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubsEnum = PhpStormStubsSingleton::getPhpStormStubs()->getEnumByHash($classHash);
        $stubsMethod = $stubsEnum->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $stubsParameter = $stubsMethod->getParameter($parameterName);
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubsMethod);
        self::assertEmpty(
            array_intersect(['int', 'float', 'string', 'bool', 'mixed', 'object'], $stubsParameter->typesFromSignature),
            "Method '$stubsEnum->fqnBasedId::$stubsMethod->name' with @since '$sinceVersion'  
                has parameter '$parameterName' with typehint '" . implode('|', $stubsParameter->typesFromSignature) .
            "' but typehints available only since php 7"
        );
    }

    #[DataProviderExternal(StubMethodsProvider::class, 'classMethodsForReturnTypeHintTestsProvider')]
    public function testClassMethodDoesNotHaveReturnTypeHint(?string $classHash, ?string $methodHash)
    {
        if (!$classHash && !$methodHash) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubClass = PhpStormStubsSingleton::getPhpStormStubs()->getClassByHash($classHash);
        $stubMethod = $stubClass->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubMethod);
        self::assertEmpty($stubMethod->returnTypesFromSignature, "Method '$stubMethod->parentId::$stubMethod->name' has since version '$sinceVersion'
            but has return typehint '" . implode('|', $stubMethod->returnTypesFromSignature) . "' that supported only since PHP 7. Please declare return type via PhpDoc");
    }

    #[DataProviderExternal(StubMethodsProvider::class, 'interfaceMethodsForReturnTypeHintTestsProvider')]
    public function testInterfaceMethodDoesNotHaveReturnTypeHint(?string $classHash, ?string $methodHash)
    {
        if (!$classHash && !$methodHash) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubInterface = PhpStormStubsSingleton::getPhpStormStubs()->getInterfaceByHash($classHash);
        $stubMethod = $stubInterface->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubMethod);
        self::assertEmpty($stubMethod->returnTypesFromSignature, "Method '$stubMethod->parentId::$stubMethod->name' has since version '$sinceVersion'
            but has return typehint '" . implode('|', $stubMethod->returnTypesFromSignature) . "' that supported only since PHP 7. Please declare return type via PhpDoc");
    }

    #[DataProviderExternal(StubMethodsProvider::class, 'enumMethodsForReturnTypeHintTestsProvider')]
    public function testEnumMethodDoesNotHaveReturnTypeHint(?string $classHash, ?string $methodHash)
    {
        if (!$classHash && !$methodHash) {
            self::markTestSkipped($this->emptyDataSetMessage);
        }
        $stubEnum = PhpStormStubsSingleton::getPhpStormStubs()->getEnumByHash($classHash);
        $stubMethod = $stubEnum->getMethod($methodHash, FunctionsFilterPredicateProvider::getMethodsByHash($methodHash));
        $sinceVersion = ParserUtils::getDeclaredSinceVersion($stubMethod);
        self::assertEmpty($stubMethod->returnTypesFromSignature, "Method '$stubMethod->parentId::$stubMethod->name' has since version '$sinceVersion'
            but has return typehint '" . implode('|', $stubMethod->returnTypesFromSignature) . "' that supported only since PHP 7. Please declare return type via PhpDoc");
    }
}
