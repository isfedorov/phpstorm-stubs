<?php
declare(strict_types=1);

namespace StubTests\TestData\Providers\Stubs;

use Exception;
use Generator;
use RuntimeException;
use StubTests\Model\EntitiesProviders\EntitiesProvider;
use StubTests\Model\PHPClass;
use StubTests\Model\PHPEnum;
use StubTests\Model\PHPFunction;
use StubTests\Model\PHPInterface;
use StubTests\Model\PHPMethod;
use StubTests\Model\Predicats\CoreEntitiesFilterPredicate;
use StubTests\Model\StubProblemType;
use StubTests\Parsers\ParserUtils;
use StubTests\TestData\Providers\EntitiesFilter;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use function in_array;

class StubMethodsProvider
{
    public static function allClassMethodsProvider(): ?Generator
    {
        $classes = EntitiesProvider::getClasses(PhpStormStubsSingleton::getPhpStormStubs());
        foreach ($classes as $className => $class) {
            foreach ($class->methods as $methodName => $method) {
                yield "method $className::$methodName [{$class->getOrCreateStubSpecificProperties()->stubObjectHash}]" => [$class->getOrCreateStubSpecificProperties()->stubObjectHash, $method->name];
            }
        }
    }

    public static function allInterfaceMethodsProvider(): ?Generator
    {
        $interfaces = EntitiesProvider::getInterfaces(PhpStormStubsSingleton::getPhpStormStubs());
        foreach ($interfaces as $className => $class) {
            foreach ($class->methods as $methodName => $method) {
                yield "method $className::$methodName" => [$class->fqnBasedId, $method->name];
            }
        }
    }

    public static function allEnumsMethodsProvider(): ?Generator
    {
        $enums = EntitiesProvider::getEnums(PhpStormStubsSingleton::getPhpStormStubs());
        foreach ($enums as $className => $class) {
            foreach ($class->methods as $methodName => $method) {
                yield "method $className::$methodName" => [$class->fqnBasedId, $method->name];
            }
        }
    }

    public static function allFunctionWithReturnTypeHintsProvider(): ?Generator
    {
        $allFunctions = EntitiesProvider::getFunctions(PhpStormStubsSingleton::getPhpStormStubs());
        $filteredFunctions = EntitiesFilter::getFiltered(
            $allFunctions,
            fn (PHPFunction $function) => empty($function->returnTypesFromSignature) || empty($function->returnTypesFromPhpDoc)
                || $function->hasTentativeReturnType || in_array('mixed', $function->returnTypesFromSignature),
            StubProblemType::TYPE_IN_PHPDOC_DIFFERS_FROM_SIGNATURE
        );
        foreach ($filteredFunctions as $functionId => $function) {
            yield "function $functionId" => [$function->getOrCreateStubSpecificProperties()->stubObjectHash];
        }
    }

    public static function allClassesMethodsWithReturnTypeHintsProvider(): ?Generator
    {
        $coreClasses = EntitiesProvider::getClasses(PhpStormStubsSingleton::getPhpStormStubs(), CoreEntitiesFilterPredicate::getCoreClasses());
        foreach (EntitiesFilter::getFiltered($coreClasses) as $class) {
            $filteredMethods = EntitiesFilter::getFiltered(
                $class->methods,
                fn (PHPMethod $method) => empty($method->returnTypesFromSignature) || empty($method->returnTypesFromPhpDoc)
                    || $method->parentId === '\___PHPSTORM_HELPERS\object' || $method->hasTentativeReturnType
                    || in_array('mixed', $method->returnTypesFromSignature),
                StubProblemType::TYPE_IN_PHPDOC_DIFFERS_FROM_SIGNATURE
            );
            foreach ($filteredMethods as $methodName => $method) {
                yield "method $class->fqnBasedId::$methodName" => [$class->fqnBasedId, $method->name];
            }
        }
    }

    public static function allInterfacesMethodsWithReturnTypeHintsProvider(): ?Generator
    {
        $coreInterfaces = EntitiesProvider::getInterfaces(PhpStormStubsSingleton::getPhpStormStubs(), CoreEntitiesFilterPredicate::getCoreInterfaces());
        foreach (EntitiesFilter::getFiltered($coreInterfaces) as $class) {
            $filteredMethods = EntitiesFilter::getFiltered(
                $class->methods,
                fn (PHPMethod $method) => empty($method->returnTypesFromSignature) || empty($method->returnTypesFromPhpDoc)
                    || $method->hasTentativeReturnType
                    || in_array('mixed', $method->returnTypesFromSignature),
                StubProblemType::TYPE_IN_PHPDOC_DIFFERS_FROM_SIGNATURE
            );
            foreach ($filteredMethods as $methodName => $method) {
                yield "method $class->fqnBasedId::$methodName" => [$class->fqnBasedId, $method->name];
            }
        }
    }

    public static function allEnumsMethodsWithReturnTypeHintsProvider(): ?Generator
    {
        $coreEnums = EntitiesProvider::getEnums(PhpStormStubsSingleton::getPhpStormStubs(), CoreEntitiesFilterPredicate::getCoreEnums());
        $array = array_filter(array_map(function (PHPEnum $enum) {
            return EntitiesFilter::getFiltered(
                $enum->methods,
                fn (PHPMethod $method) => empty($method->returnTypesFromSignature) || empty($method->returnTypesFromPhpDoc)
                    || $method->hasTentativeReturnType
                    || in_array('mixed', $method->returnTypesFromSignature),
                StubProblemType::TYPE_IN_PHPDOC_DIFFERS_FROM_SIGNATURE
            );
        }, $coreEnums), fn ($arr) => !empty($arr));
        if (empty($array)) {
            yield [null, null];
        }else {
            foreach (EntitiesFilter::getFiltered($coreEnums) as $class) {
                $filteredMethods = EntitiesFilter::getFiltered(
                    $class->methods,
                    fn (PHPMethod $method) => empty($method->returnTypesFromSignature) || empty($method->returnTypesFromPhpDoc)
                        || $method->hasTentativeReturnType
                        || in_array('mixed', $method->returnTypesFromSignature),
                    StubProblemType::TYPE_IN_PHPDOC_DIFFERS_FROM_SIGNATURE
                );
                foreach ($filteredMethods as $methodName => $method) {
                    yield "method $class->id::$methodName" => [$class->id, $method->name];
                }
            }
        }
    }

    public static function classMethodsForReturnTypeHintTestsProvider(): ?Generator
    {
        $filterFunction = self::getFilterFunctionForLanguageLevel(PHPClass::class, 7);
        return self::yieldFilteredMethods(
            PHPClass::class,
            $filterFunction,
            StubProblemType::FUNCTION_HAS_RETURN_TYPEHINT,
            StubProblemType::WRONG_RETURN_TYPEHINT
        );
    }

    public static function interfaceMethodsForReturnTypeHintTestsProvider(): ?Generator
    {
        $filterFunction = self::getFilterFunctionForLanguageLevel(PHPInterface::class, 7);
        return self::yieldFilteredMethods(
            PHPInterface::class,
            $filterFunction,
            StubProblemType::FUNCTION_HAS_RETURN_TYPEHINT,
            StubProblemType::WRONG_RETURN_TYPEHINT
        );
    }

    public static function enumMethodsForReturnTypeHintTestsProvider(): ?Generator
    {
        $filterFunction = self::getFilterFunctionForLanguageLevel(PHPEnum::class, 7);
        return self::yieldFilteredMethods(
            PHPEnum::class,
            $filterFunction,
            StubProblemType::FUNCTION_HAS_RETURN_TYPEHINT,
            StubProblemType::WRONG_RETURN_TYPEHINT
        );
    }

    public static function classMethodsForNullableReturnTypeHintTestsProvider(): ?Generator
    {
        $filterFunction = self::getFilterFunctionForLanguageLevel(PHPClass::class, 7.1);
        return self::yieldFilteredMethods(
            PHPClass::class,
            $filterFunction,
            StubProblemType::HAS_NULLABLE_TYPEHINT,
            StubProblemType::WRONG_RETURN_TYPEHINT
        );
    }

    public static function interfaceMethodsForNullableReturnTypeHintTestsProvider(): ?Generator
    {
        $filterFunction = self::getFilterFunctionForLanguageLevel(PHPInterface::class, 7.1);
        return self::yieldFilteredMethods(
            PHPInterface::class,
            $filterFunction,
            StubProblemType::HAS_NULLABLE_TYPEHINT,
            StubProblemType::WRONG_RETURN_TYPEHINT
        );
    }

    public static function enumMethodsForNullableReturnTypeHintTestsProvider(): ?Generator
    {
        $filterFunction = self::getFilterFunctionForLanguageLevel(PHPEnum::class, 7.1);
        return self::yieldFilteredMethods(
            PHPEnum::class,
            $filterFunction,
            StubProblemType::HAS_NULLABLE_TYPEHINT,
            StubProblemType::WRONG_RETURN_TYPEHINT
        );
    }

    public static function classMethodsForUnionReturnTypeHintTestsProvider(): ?Generator
    {
        $filterFunction = self::getFilterFunctionForLanguageLevel(PHPClass::class, 8);
        return self::yieldFilteredMethods(
            PHPClass::class,
            $filterFunction,
            StubProblemType::HAS_UNION_TYPEHINT,
            StubProblemType::WRONG_RETURN_TYPEHINT
        );
    }

    public static function interfaceMethodsForUnionReturnTypeHintTestsProvider(): ?Generator
    {
        $filterFunction = self::getFilterFunctionForLanguageLevel(PHPInterface::class, 8);
        return self::yieldFilteredMethods(
            PHPInterface::class,
            $filterFunction,
            StubProblemType::HAS_UNION_TYPEHINT,
            StubProblemType::WRONG_RETURN_TYPEHINT
        );
    }

    public static function enumMethodsForUnionReturnTypeHintTestsProvider(): ?Generator
    {
        $filterFunction = self::getFilterFunctionForLanguageLevel(PHPEnum::class, 8);
        return self::yieldFilteredMethods(
            PHPEnum::class,
            $filterFunction,
            StubProblemType::HAS_UNION_TYPEHINT,
            StubProblemType::WRONG_RETURN_TYPEHINT
        );
    }

    private static function getFilterFunctionForLanguageLevel(string $classType, float $languageVersion): callable
    {
        return match ($classType) {
            PHPClass::class => fn (PHPClass $class, PHPMethod $method, ?float $firstSinceVersion) => !$method->isFinal &&
                !$class->isFinal && $firstSinceVersion !== null && $firstSinceVersion < $languageVersion && !$method->isReturnTypeTentative,
            PHPInterface::class => fn (PHPInterface $class, PHPMethod $method, ?float $firstSinceVersion) => !$method->isFinal &&
                !$class->isFinal && $firstSinceVersion !== null && $firstSinceVersion < $languageVersion && !$method->isReturnTypeTentative,
            PHPEnum::class => fn (PHPEnum $class, PHPMethod $method, ?float $firstSinceVersion) => !$method->isFinal &&
                !$class->isFinal && $firstSinceVersion !== null && $firstSinceVersion < $languageVersion && !$method->isReturnTypeTentative,
            default => throw new Exception("Unknown class type"),
        };
    }

    /**
     * @throws RuntimeException
     */
    private static function yieldFilteredMethods(string $classType, callable $filterFunction, int ...$problemTypes): ?Generator
    {
        $classes = match ($classType) {
            PHPClass::class => EntitiesProvider::getClasses(PhpStormStubsSingleton::getPhpStormStubs(), CoreEntitiesFilterPredicate::getCoreClasses()),
            PHPInterface::class => EntitiesProvider::getInterfaces(PhpStormStubsSingleton::getPhpStormStubs(), CoreEntitiesFilterPredicate::getCoreInterfaces()),
            PHPEnum::class => EntitiesProvider::getEnums(PhpStormStubsSingleton::getPhpStormStubs(), CoreEntitiesFilterPredicate::getCoreEnums()),
            default => throw new Exception("Unknown class type")
        };
        $filtered = EntitiesFilter::getFiltered($classes);
        $array = array_filter(array_map(function ($class) use ($problemTypes, $filterFunction) {
            return array_filter(EntitiesFilter::getFiltered(
                $class->methods,
                fn (PHPMethod $method) => $method->parentId === '\___PHPSTORM_HELPERS\object',
                ...$problemTypes
            ), function ($method) use ($filterFunction, $class) {
                $firstSinceVersion = ParserUtils::getDeclaredSinceVersion($method);
                return $filterFunction($class, $method, $firstSinceVersion) === true;
            });
        }, $filtered), fn ($arr) => !empty($arr));
        if (empty($array)) {
            yield [null, null];
        } else {
            foreach ($array as $classId => $methods) {
                $class = $classes[$classId];
                /** @var PHPMethod $method */
                foreach ($methods as $methodName => $method) {
                    yield "method $classId::$methodName" => [$class->getOrCreateStubSpecificProperties()->stubObjectHash, $method->getOrCreateStubSpecificProperties()->stubObjectHash];
                }
            }
        }
    }
}
