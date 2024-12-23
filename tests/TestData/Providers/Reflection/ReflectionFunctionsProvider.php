<?php
declare(strict_types=1);

namespace StubTests\TestData\Providers\Reflection;

use Generator;
use StubTests\Model\EntitiesProviders\EntitiesProvider;
use StubTests\Model\StubProblemType;
use StubTests\TestData\Providers\EntitiesFilter;
use StubTests\TestData\Providers\ReflectionStubsSingleton;

class ReflectionFunctionsProvider
{
    public static function allFunctionsProvider(): ?Generator
    {
        $filtered = EntitiesFilter::getFiltered(EntitiesProvider::getFunctions(ReflectionStubsSingleton::getReflectionStubs()), null, StubProblemType::HAS_DUPLICATION);
        if (empty($filtered)) {
            yield [null];
        } else {
            foreach ($filtered as $function) {
                yield "function $function->fqnBasedId" => [$function->fqnBasedId];
            }
        }
    }

    public static function functionsForReturnTypeHintsTestProvider(): ?Generator
    {
        $filtered = EntitiesFilter::getFiltered(
            EntitiesProvider::getFunctions(ReflectionStubsSingleton::getReflectionStubs()),
            null,
            StubProblemType::WRONG_RETURN_TYPEHINT,
            StubProblemType::HAS_DUPLICATION
        );
        if (empty($filtered)) {
            yield [null];
        } else {
            foreach ($filtered as $function) {
                yield "function $function->fqnBasedId" => [$function->fqnBasedId];
            }
        }
    }

    public static function functionsForDeprecationTestsProvider(): ?Generator
    {
        $filtered = EntitiesFilter::getFiltered(
            EntitiesProvider::getFunctions(ReflectionStubsSingleton::getReflectionStubs()),
            null,
            StubProblemType::FUNCTION_IS_DEPRECATED,
            StubProblemType::HAS_DUPLICATION
        );
        if (empty($filtered)) {
            yield [null];
        } else {
            foreach ($filtered as $function) {
                yield "function $function->fqnBasedId" => [$function->fqnBasedId];
            }
        }
    }

    public static function functionsForParamsAmountTestsProvider(): ?Generator
    {
        $filtered = EntitiesFilter::getFiltered(
            EntitiesProvider::getFunctions(ReflectionStubsSingleton::getReflectionStubs()),
            null,
            StubProblemType::FUNCTION_PARAMETER_MISMATCH,
            StubProblemType::HAS_DUPLICATION
        );
        if (empty($filtered)) {
            yield [null];
        } else {
            foreach ($filtered as $function) {
                yield "function $function->fqnBasedId" => [$function->fqnBasedId];
            }
        }
    }
}
