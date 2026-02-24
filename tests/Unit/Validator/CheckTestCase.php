<?php

namespace StubTests\Unit\Validator;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Model\Types\NoType;
use StubTests\Sources\Parsers\Entities\Model\Types\NullableType;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Parsers\Entities\Model\Types\UnionType;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\ReflectionProviderInterface;

/**
 * Base test case providing helper methods for creating mock entities.
 */
abstract class CheckTestCase extends TestCase
{
    /**
     * Create a mock ParsedDataStorageManager.
     */
    protected function createMockStorageManager(): ParsedDataStorageManager
    {
        return $this->createMock(ParsedDataStorageManager::class);
    }

    /**
     * Create a mock PHPFunction with the given id/name.
     */
    protected function createMockFunction(string $name, array $parameters = [], $returnType = null): PHPFunction
    {
        $function = $this->getMockBuilder(PHPFunction::class)
            ->disableOriginalConstructor()
            ->addMethods(['getReturnType'])
            ->onlyMethods(['getId', 'getName', 'getParameters', 'getReturnTypeFromSignature'])
            ->getMock();

        $function->method('getId')->willReturn($name);
        $function->method('getName')->willReturn($name);
        $function->method('getParameters')->willReturn($parameters);

        if ($returnType !== null) {
            $function->method('getReturnTypeFromSignature')->willReturn($returnType);
            $function->method('getReturnType')->willReturn($returnType);
        }

        return $function;
    }

    /**
     * Create a mock PHPClass with the given id/name.
     */
    protected function createMockClass(string $name, array $methods = []): PHPClass
    {
        $class = $this->createMock(PHPClass::class);
        $class->method('getId')->willReturn($name);
        $class->method('getName')->willReturn($name);
        $class->method('getMethods')->willReturn($methods);

        return $class;
    }

    /**
     * Create a mock PHPClass with properties (isFinal, isReadonly, namespace, parentClass).
     *
     * @param string $name Class name/ID
     * @param string|null $namespace Class namespace
     * @param bool|null $isFinal Whether class is final
     * @param bool|null $isReadonly Whether class is readonly
     * @param array $methods Array of methods
     * @param PHPClass|null $parentClass Parent class object
     * @return PHPClass
     */
    protected function createMockClassWithProperties(
        string $name,
        ?string $namespace = null,
        ?bool $isFinal = null,
        ?bool $isReadonly = null,
        array $methods = [],
        ?PHPClass $parentClass = null
    ): PHPClass {
        $class = $this->getMockBuilder(PHPClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getName', 'getNamespace', 'getMethods'])
            ->getMock();

        $class->method('getId')->willReturn($name);
        $class->method('getName')->willReturn($name);
        $class->method('getNamespace')->willReturn($namespace);
        $class->method('getMethods')->willReturn($methods);

        // Set public properties
        if ($isFinal !== null) {
            $class->isFinal = $isFinal;
        }
        if ($isReadonly !== null) {
            $class->isReadonly = $isReadonly;
        }
        if ($parentClass !== null) {
            $class->parentClass = $parentClass;
        }

        return $class;
    }

    /**
     * Create a mock PHPMethod with the given name.
     */
    protected function createMockMethod(string $name, array $parameters = [], $returnType = null): PHPMethod
    {
        $method = $this->getMockBuilder(PHPMethod::class)
            ->disableOriginalConstructor()
            ->addMethods(['getReturnType'])
            ->onlyMethods(['getName', 'getParameters', 'getReturnTypeFromSignature'])
            ->getMock();

        $method->method('getName')->willReturn($name);
        $method->method('getParameters')->willReturn($parameters);

        if ($returnType !== null) {
            $method->method('getReturnTypeFromSignature')->willReturn($returnType);
            $method->method('getReturnType')->willReturn($returnType);
        }

        return $method;
    }

    /**
     * Create a mock PHPParameter with the given name and type.
     *
     * Note: Supports both getDeclaredType() and getType() methods to be compatible
     * with different validators (ParameterNamesCheck uses getName, ParameterTypesCheck uses getType).
     *
     * @param string $name Parameter name
     * @param mixed|null $type Parameter type (optional)
     * @param string|null $sinceVersion Version when parameter was introduced (optional)
     * @param string|null $removedVersion Version when parameter was removed (optional)
     */
    protected function createMockParameter(string $name, $type = null, ?string $sinceVersion = null, ?string $removedVersion = null): PHPParameter
    {
        $parameter = $this->getMockBuilder(PHPParameter::class)
            ->disableOriginalConstructor()
            ->addMethods(['getType'])
            ->onlyMethods(['getName', 'getDeclaredType', 'getSinceVersion', 'getRemovedVersion', 'isVariadic', 'isOptional', 'isPassedByReference'])
            ->getMock();

        $parameter->method('getName')->willReturn($name);
        $parameter->method('getSinceVersion')->willReturn($sinceVersion);
        $parameter->method('getRemovedVersion')->willReturn($removedVersion);

        // Mock typed property accessor methods with default values
        $parameter->method('isVariadic')->willReturn(false);
        $parameter->method('isOptional')->willReturn(false);
        $parameter->method('isPassedByReference')->willReturn(false);

        // Support both getDeclaredType() and getType() for different validators
        if ($type !== null) {
            $parameter->method('getDeclaredType')->willReturn($type);
            $parameter->method('getType')->willReturn($type);
        } else {
            $noType = new NoType();
            $parameter->method('getDeclaredType')->willReturn($noType);
            $parameter->method('getType')->willReturn($noType);
        }

        return $parameter;
    }

    /**
     * Create a StandaloneType with the given type name.
     */
    protected function createType(string $typeName): StandaloneType
    {
        return new StandaloneType($typeName);
    }

    /**
     * Create a mock type that returns a string representation.
     */
    protected function createMockType(string $typeString)
    {
        $type = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__toString', 'getTypeName'])
            ->getMock();

        $type->method('__toString')->willReturn($typeString);
        $type->method('getTypeName')->willReturn($typeString);

        return $type;
    }

    /**
     * Create a UnionType with the given types (e.g., 'string|int').
     */
    protected function createUnionType(string ...$types): UnionType
    {
        $unionType = new UnionType();
        foreach ($types as $type) {
            $unionType->addType(new StandaloneType($type));
        }
        return $unionType;
    }

    /**
     * Create a NullableType with the given base type (e.g., '?string').
     */
    protected function createNullableType(string $baseType): NullableType
    {
        $nullableType = new NullableType();
        $nullableType->addBasicType(new StandaloneType($baseType));
        return $nullableType;
    }

    /**
     * Create a mock ReflectionProviderInterface that returns the given storage manager.
     *
     * @param array $functions Array of PHPFunction mocks to return
     * @param array $classes Array of PHPClass mocks to return
     * @return ReflectionProviderInterface
     */
    protected function createMockReflectionProvider(array $functions = [], array $classes = []): ReflectionProviderInterface
    {
        $provider = $this->createMock(ReflectionProviderInterface::class);
        $manager = $this->createMockStorageManager();

        $manager->method('getFunctions')->willReturn($functions);
        $manager->method('getClasses')->willReturn($classes);

        $provider->method('getReflection')->willReturn($manager);

        return $provider;
    }
}
