<?php

namespace StubTests\Unit\Validator;

use PHPUnit\Framework\TestCase;
use StubTests\Framework\Parsers\Entities\Model\Access\AccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\PrivateAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\ProtectedAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\PublicAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
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
            ->onlyMethods(['getId', 'getName', 'getParameters', 'getReturnTypeFromSignature'])
            ->getMock();

        $function->method('getId')->willReturn($name);
        $function->method('getName')->willReturn($name);
        $function->method('getParameters')->willReturn($parameters);

        if ($returnType !== null) {
            $function->method('getReturnTypeFromSignature')->willReturn($returnType);
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
        ?PHPClass $parentClass = null,
        array $interfaces = [],
        array $properties = []
    ): PHPClass {
        $class = $this->getMockBuilder(PHPClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getName', 'getNamespace', 'getMethods', 'getProperties'])
            ->getMock();

        $class->method('getId')->willReturn($name);
        $class->method('getName')->willReturn($name);
        $class->method('getNamespace')->willReturn($namespace);
        $class->method('getMethods')->willReturn($methods);
        $class->method('getProperties')->willReturn($properties);

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
        if (!empty($interfaces)) {
            $class->interfaces = $interfaces;
        }

        return $class;
    }

    /**
     * Create a mock PHPProperty with the given name and optional version bounds.
     */
    protected function createMockProperty(string $name, ?string $sinceVersion = null, ?string $removedVersion = null): PHPProperty
    {
        $property = $this->getMockBuilder(PHPProperty::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'getSinceVersion', 'getRemovedVersion'])
            ->getMock();

        $property->method('getName')->willReturn($name);
        $property->method('getSinceVersion')->willReturn($sinceVersion);
        $property->method('getRemovedVersion')->willReturn($removedVersion);

        return $property;
    }

    /**
     * Create a mock PHPMethod with the given name.
     */
    protected function createMockMethod(string $name, array $parameters = [], $returnType = null): PHPMethod
    {
        $method = $this->getMockBuilder(PHPMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'getParameters', 'getReturnTypeFromSignature'])
            ->getMock();

        $method->method('getName')->willReturn($name);
        $method->method('getParameters')->willReturn($parameters);

        if ($returnType !== null) {
            $method->method('getReturnTypeFromSignature')->willReturn($returnType);
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
            ->onlyMethods(['getName', 'getDeclaredType', 'getSinceVersion', 'getRemovedVersion', 'isVariadic', 'isOptional', 'isPassedByReference'])
            ->getMock();

        $parameter->method('getName')->willReturn($name);
        $parameter->method('getSinceVersion')->willReturn($sinceVersion);
        $parameter->method('getRemovedVersion')->willReturn($removedVersion);

        $parameter->method('isVariadic')->willReturn(false);
        $parameter->method('isOptional')->willReturn(false);
        $parameter->method('isPassedByReference')->willReturn(false);

        $parameter->method('getDeclaredType')->willReturn($type ?? new NoType());

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
    protected function createMockType(string $typeString): object
    {
        return new class($typeString) {
            public function __construct(private readonly string $typeName) {}
            public function __toString(): string { return $this->typeName; }
            public function getTypeName(): string { return $this->typeName; }
        };
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
        return new NullableType(new StandaloneType($baseType));
    }

    /**
     * Convert a string access modifier to an AccessModifier object.
     * Helper for test compatibility.
     *
     * @param string $access One of: 'public', 'protected', 'private'
     * @return AccessModifier
     */
    protected function createAccessModifier(string $access): AccessModifier
    {
        return match ($access) {
            'private' => new PrivateAccessModifier(),
            'protected' => new ProtectedAccessModifier(),
            default => new PublicAccessModifier(),
        };
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
