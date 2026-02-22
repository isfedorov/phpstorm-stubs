<?php

namespace StubTests\Unit\Validator;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Model\Types\NoType;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Parsers\ParsedDataStorageManager;

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
        $function = $this->createMock(PHPFunction::class);
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
     * Create a mock PHPMethod with the given name.
     */
    protected function createMockMethod(string $name, array $parameters = [], $returnType = null): PHPMethod
    {
        $method = $this->createMock(PHPMethod::class);
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
     */
    protected function createMockParameter(string $name, $type = null): PHPParameter
    {
        $parameter = $this->createMock(PHPParameter::class);
        $parameter->method('getName')->willReturn($name);

        if ($type !== null) {
            $parameter->method('getDeclaredType')->willReturn($type);
        } else {
            $parameter->method('getDeclaredType')->willReturn(new NoType());
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
}
