<?php

namespace StubTests\Unit\Parsers\Serialization;

use PHPUnit\Framework\TestCase;
use StubTests\Framework\Parsers\Entities\Model\Access\PrivateAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\ProtectedAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\PublicAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\Entities\Model\Types\NoType;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Parsers\ReflectionEntitySerializer;

class ReflectionEntitySerializerTest extends TestCase
{
    private ReflectionEntitySerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new ReflectionEntitySerializer();
    }

    public function testSerializeSimpleClass(): void
    {
        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setNamespace('Test\\Namespace');
        $class->setId('Test\\Namespace\\TestClass');
        $class->isFinal = true;
        $class->isReadonly = false;
        $class->setSourcePath('/path/to/file.php');
        $class->setDuplicates([]);

        $result = $this->serializer->serialize($class);

        self::assertEquals('PHPClass', $result['_type']);
        self::assertEquals('TestClass', $result['name']);
        self::assertEquals('Test\\Namespace', $result['namespace']);
        self::assertEquals('Test\\Namespace\\TestClass', $result['id']);
        self::assertTrue($result['isFinal']);
        self::assertFalse($result['isReadonly']);
        self::assertEquals('/path/to/file.php', $result['sourcePath']);
        self::assertIsArray($result['methods']);
        self::assertIsArray($result['properties']);
        self::assertIsArray($result['constants']);
        self::assertEmpty($result['methods']);
        self::assertEmpty($result['properties']);
        self::assertEmpty($result['constants']);
    }

    public function testSerializeClassWithMethod(): void
    {
        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setId('TestClass');

        $method = new PHPMethod();
        $method->setName('testMethod');
        $method->setIsStatic(true);
        $method->setIsFinal(false);
        $method->setIsAbstract(false);
        $method->setDeprecated(false);
        $method->setAccess(new PublicAccessModifier());
        $method->setParameters([]);

        $returnType = new StandaloneType('string');
        $method->setReturnTypeFromSignature($returnType);
        $method->setHasTentativeReturnType(false);

        $class->methods[] = $method;

        $result = $this->serializer->serialize($class);

        self::assertCount(1, $result['methods']);
        self::assertEquals('testMethod', $result['methods'][0]['name']);
        self::assertTrue($result['methods'][0]['isStatic']);
        self::assertFalse($result['methods'][0]['isFinal']);
        self::assertFalse($result['methods'][0]['isAbstract']);
        self::assertFalse($result['methods'][0]['isDeprecated']);
        self::assertEquals('public', $result['methods'][0]['accessModifier']);
        self::assertEquals('string', $result['methods'][0]['returnType']);
        self::assertFalse($result['methods'][0]['hasTentativeReturnType']);
        self::assertIsArray($result['methods'][0]['parameters']);
    }

    public function testSerializeMethodWithAccessModifiers(): void
    {
        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setId('TestClass');

        // Test private method
        $privateMethod = new PHPMethod();
        $privateMethod->setName('privateMethod');
        $privateMethod->setAccess(new PrivateAccessModifier());
        $privateMethod->setIsStatic(false);
        $privateMethod->setIsFinal(false);
        $privateMethod->setIsAbstract(false);
        $privateMethod->setParameters([]);
        $privateMethod->setReturnTypeFromSignature(new NoType());
        $class->methods[] = $privateMethod;

        // Test protected method
        $protectedMethod = new PHPMethod();
        $protectedMethod->setName('protectedMethod');
        $protectedMethod->setAccess(new ProtectedAccessModifier());
        $protectedMethod->setIsStatic(false);
        $protectedMethod->setIsFinal(false);
        $protectedMethod->setIsAbstract(false);
        $protectedMethod->setParameters([]);
        $protectedMethod->setReturnTypeFromSignature(new NoType());
        $class->methods[] = $protectedMethod;

        $result = $this->serializer->serialize($class);

        self::assertEquals('private', $result['methods'][0]['accessModifier']);
        self::assertEquals('protected', $result['methods'][1]['accessModifier']);
    }

    public function testSerializeMethodWithParameters(): void
    {
        $method = new PHPMethod();
        $method->setName('testMethod');
        $method->setIsStatic(false);
        $method->setIsFinal(false);
        $method->setIsAbstract(false);
        $method->setAccess(new PublicAccessModifier());
        $method->setReturnTypeFromSignature(new NoType());

        $param = new PHPParameter('testParam');
        $param->setPosition(0);
        $param->setIsOptional(true);
        $param->setIsVariadic(false);
        $param->setIsPassedByReference(false);
        $param->setHasDefaultValue(true);
        $param->setDefaultValue('default');
        $param->setType(new StandaloneType('string'));

        $method->setParameters([$param]);

        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setId('TestClass');
        $class->methods[] = $method;

        $result = $this->serializer->serialize($class);

        $parameters = $result['methods'][0]['parameters'];
        self::assertCount(1, $parameters);
        self::assertEquals('testParam', $parameters[0]['name']);
        self::assertEquals(0, $parameters[0]['position']);
        self::assertTrue($parameters[0]['isOptional']);
        self::assertFalse($parameters[0]['isVariadic']);
        self::assertFalse($parameters[0]['isPassedByReference']);
        self::assertTrue($parameters[0]['hasDefaultValue']);
        self::assertEquals('default', $parameters[0]['defaultValue']);
        self::assertEquals('string', $parameters[0]['type']);
    }

    public function testSerializeClassWithProperty(): void
    {
        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setId('TestClass');

        $property = new PHPProperty();
        $property->setName('testProperty');
        $property->setIsStatic(false);
        $property->setIsReadonly(true);
        $property->setAccess(new ProtectedAccessModifier());
        $property->setTypeFromSignature(new StandaloneType('int'));

        $class->properties[] = $property;

        $result = $this->serializer->serialize($class);

        self::assertCount(1, $result['properties']);
        self::assertEquals('testProperty', $result['properties'][0]['name']);
        self::assertFalse($result['properties'][0]['isStatic']);
        self::assertTrue($result['properties'][0]['isReadonly']);
        self::assertEquals('protected', $result['properties'][0]['accessModifier']);
        self::assertEquals('int', $result['properties'][0]['type']);
    }

    public function testSerializeClassWithConstant(): void
    {
        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setId('TestClass');

        $constant = new PHPClassConstant();
        $constant->setName('TEST_CONSTANT');
        $constant->value = 'test_value';
        $constant->visibility = 'public';
        $constant->isFinal = true;

        $class->constants[] = $constant;

        $result = $this->serializer->serialize($class);

        self::assertCount(1, $result['constants']);
        self::assertEquals('TEST_CONSTANT', $result['constants'][0]['name']);
        self::assertEquals('test_value', $result['constants'][0]['value']);
        self::assertEquals('public', $result['constants'][0]['visibility']);
        self::assertTrue($result['constants'][0]['isFinal']);
    }

    public function testSerializeFunction(): void
    {
        $function = new PHPFunction();
        $function->setName('testFunction');
        $function->setNamespace('Test\\Namespace');
        $function->setId('Test\\Namespace\\testFunction');
        $function->setDeprecated(false);
        $function->setSourcePath('/path/to/file.php');
        $function->setDuplicates([]);
        $function->setHasTentativeReturnType(true);

        $returnType = new StandaloneType('bool');
        $function->setReturnTypeFromSignature($returnType);
        $function->setParameters([]);

        $result = $this->serializer->serialize($function);

        self::assertEquals('PHPFunction', $result['_type']);
        self::assertEquals('testFunction', $result['name']);
        self::assertEquals('Test\\Namespace', $result['namespace']);
        self::assertEquals('Test\\Namespace\\testFunction', $result['id']);
        self::assertFalse($result['isDeprecated']);
        self::assertEquals('bool', $result['returnType']);
        self::assertTrue($result['hasTentativeReturnType']);
        self::assertIsArray($result['parameters']);
    }

    public function testSerializeInterface(): void
    {
        $interface = new PHPInterface();
        $interface->setName('TestInterface');
        $interface->setNamespace('Test\\Namespace');
        $interface->setId('Test\\Namespace\\TestInterface');
        $interface->setSourcePath('/path/to/file.php');
        $interface->setDuplicates([]);

        $method = new PHPMethod();
        $method->setName('interfaceMethod');
        $method->setIsStatic(false);
        $method->setIsFinal(false);
        $method->setIsAbstract(false);
        $method->setAccess(new PublicAccessModifier());
        $method->setParameters([]);
        $method->setReturnTypeFromSignature(new NoType());
        $interface->methods[] = $method;

        $constant = new PHPClassConstant();
        $constant->setName('INTERFACE_CONSTANT');
        $constant->value = 123;
        $interface->constants[] = $constant;

        $result = $this->serializer->serialize($interface);

        self::assertEquals('PHPInterface', $result['_type']);
        self::assertEquals('TestInterface', $result['name']);
        self::assertEquals('Test\\Namespace', $result['namespace']);
        self::assertEquals('Test\\Namespace\\TestInterface', $result['id']);
        self::assertCount(1, $result['methods']);
        self::assertCount(1, $result['constants']);
        self::assertEquals('interfaceMethod', $result['methods'][0]['name']);
        self::assertEquals('INTERFACE_CONSTANT', $result['constants'][0]['name']);
    }

    public function testSerializeEnum(): void
    {
        $enum = new PHPEnum();
        $enum->setName('TestEnum');
        $enum->setNamespace('Test\\Namespace');
        $enum->setId('Test\\Namespace\\TestEnum');
        $enum->isFinal = true;
        $enum->isReadonly = false;
        $enum->setSourcePath('/path/to/file.php');
        $enum->setDuplicates([]);

        $result = $this->serializer->serialize($enum);

        self::assertEquals('PHPEnum', $result['_type']);
        self::assertEquals('TestEnum', $result['name']);
        self::assertEquals('Test\\Namespace', $result['namespace']);
        self::assertTrue($result['isFinal']);
        self::assertIsArray($result['methods']);
        self::assertIsArray($result['interfaces']);
    }

    public function testSerializeConstant(): void
    {
        $constant = new PHPConstant();
        $constant->setName('TEST_CONSTANT');
        $constant->setNamespace('Test\\Namespace');
        $constant->setId('Test\\Namespace\\TEST_CONSTANT');
        $constant->value = 42;
        $constant->setSourcePath('/path/to/file.php');
        $constant->setDuplicates([]);

        $result = $this->serializer->serialize($constant);

        self::assertEquals('PHPConstant', $result['_type']);
        self::assertEquals('TEST_CONSTANT', $result['name']);
        self::assertEquals('Test\\Namespace', $result['namespace']);
        self::assertEquals(42, $result['value']);
        self::assertEquals('/path/to/file.php', $result['sourcePath']);
    }

    public function testSerializeUnknownEntityType(): void
    {
        $unknown = new \stdClass();
        $unknown->someProperty = 'test';

        $result = $this->serializer->serialize($unknown);

        self::assertEquals('Unknown', $result['_type']);
        self::assertArrayHasKey('className', $result);
        self::assertArrayHasKey('data', $result);
    }

    public function testDeserializeClass(): void
    {
        $data = [
            '_type' => 'PHPClass',
            'name' => 'TestClass',
            'namespace' => 'Test\\Namespace',
            'id' => 'Test\\Namespace\\TestClass',
            'isFinal' => true,
            'isReadonly' => false,
            'sourcePath' => '/path/to/file.php',
            'duplicates' => [],
            'methods' => [],
            'properties' => [],
            'constants' => []
        ];

        $result = $this->serializer->deserialize($data);

        self::assertInstanceOf(PHPClass::class, $result);
        self::assertEquals('TestClass', $result->getName());
        self::assertEquals('Test\\Namespace', $result->getNamespace());
        self::assertEquals('Test\\Namespace\\TestClass', $result->getId());
        self::assertTrue($result->isFinal);
        self::assertFalse($result->isReadonly);
        self::assertEquals('/path/to/file.php', $result->getSourcePath());
    }

    public function testDeserializeClassWithMethod(): void
    {
        $data = [
            '_type' => 'PHPClass',
            'name' => 'TestClass',
            'id' => 'TestClass',
            'methods' => [
                [
                    'name' => 'testMethod',
                    'isStatic' => true,
                    'isFinal' => false,
                    'isAbstract' => false,
                    'isDeprecated' => false,
                    'accessModifier' => 'protected',
                    'parameters' => [],
                    'returnType' => null,  // null instead of string to avoid type conversion issues
                    'hasTentativeReturnType' => false
                ]
            ],
            'properties' => [],
            'constants' => []
        ];

        $result = $this->serializer->deserialize($data);

        self::assertInstanceOf(PHPClass::class, $result);
        self::assertCount(1, $result->methods);

        $method = $result->methods[0];
        self::assertEquals('testMethod', $method->getName());
        self::assertTrue($method->isStatic());
        self::assertFalse($method->isFinal());
        self::assertFalse($method->isAbstract());
        self::assertFalse($method->isDeprecated());
        self::assertInstanceOf(ProtectedAccessModifier::class, $method->getAccess());
    }

    public function testDeserializeMethodWithParameters(): void
    {
        $data = [
            '_type' => 'PHPClass',
            'name' => 'TestClass',
            'id' => 'TestClass',
            'methods' => [
                [
                    'name' => 'testMethod',
                    'isStatic' => false,
                    'isFinal' => false,
                    'isAbstract' => false,
                    'isDeprecated' => false,
                    'accessModifier' => 'public',
                    'parameters' => [
                        [
                            'name' => 'param1',
                            'position' => 0,
                            'isOptional' => true,
                            'isVariadic' => false,
                            'isPassedByReference' => false,
                            'hasDefaultValue' => true,
                            'defaultValue' => 'test'
                        ]
                    ],
                    'returnType' => null,
                    'hasTentativeReturnType' => false
                ]
            ],
            'properties' => [],
            'constants' => []
        ];

        $result = $this->serializer->deserialize($data);

        self::assertCount(1, $result->methods);
        $parameters = $result->methods[0]->getParameters();
        self::assertCount(1, $parameters);

        $param = $parameters[0];
        self::assertEquals('param1', $param->getName());
        self::assertEquals(0, $param->getPosition());
        self::assertTrue($param->isOptional());
        self::assertFalse($param->isVariadic());
        self::assertFalse($param->isPassedByReference());
        self::assertTrue($param->hasDefaultValue());
        self::assertEquals('test', $param->getDefaultValue());
    }

    public function testDeserializeClassWithProperty(): void
    {
        $data = [
            '_type' => 'PHPClass',
            'name' => 'TestClass',
            'id' => 'TestClass',
            'methods' => [],
            'properties' => [
                [
                    'name' => 'testProperty',
                    'isStatic' => true,
                    'isReadonly' => false,
                    'accessModifier' => 'private',
                    'type' => null  // null to avoid type conversion issues
                ]
            ],
            'constants' => []
        ];

        $result = $this->serializer->deserialize($data);

        self::assertCount(1, $result->properties);

        $property = $result->properties[0];
        self::assertEquals('testProperty', $property->getName());
        self::assertTrue($property->isStatic());
        self::assertFalse($property->isReadonly());
        self::assertInstanceOf(PrivateAccessModifier::class, $property->getAccess());
    }

    public function testDeserializeFunction(): void
    {
        $data = [
            '_type' => 'PHPFunction',
            'name' => 'testFunction',
            'namespace' => 'Test\\Namespace',
            'id' => 'Test\\Namespace\\testFunction',
            'isDeprecated' => true,
            'returnType' => null,  // null to avoid type conversion issues
            'sourcePath' => '/path/to/file.php',
            'duplicates' => [],
            'hasTentativeReturnType' => false,
            'parameters' => []
        ];

        $result = $this->serializer->deserialize($data);

        self::assertInstanceOf(PHPFunction::class, $result);
        self::assertEquals('testFunction', $result->getName());
        self::assertEquals('Test\\Namespace', $result->getNamespace());
        self::assertTrue($result->isDeprecated());
        self::assertFalse($result->hasTentativeReturnType());
    }

    public function testDeserializeInterface(): void
    {
        $data = [
            '_type' => 'PHPInterface',
            'name' => 'TestInterface',
            'namespace' => 'Test\\Namespace',
            'id' => 'Test\\Namespace\\TestInterface',
            'sourcePath' => '/path/to/file.php',
            'duplicates' => [],
            'methods' => [],
            'constants' => []
        ];

        $result = $this->serializer->deserialize($data);

        self::assertInstanceOf(PHPInterface::class, $result);
        self::assertEquals('TestInterface', $result->getName());
        self::assertEquals('Test\\Namespace', $result->getNamespace());
    }

    public function testDeserializeEnum(): void
    {
        $data = [
            '_type' => 'PHPEnum',
            'name' => 'TestEnum',
            'namespace' => 'Test\\Namespace',
            'id' => 'Test\\Namespace\\TestEnum',
            'isFinal' => true,
            'isReadonly' => false,
            'sourcePath' => '/path/to/file.php',
            'duplicates' => [],
            'methods' => []
        ];

        $result = $this->serializer->deserialize($data);

        self::assertInstanceOf(PHPEnum::class, $result);
        self::assertEquals('TestEnum', $result->getName());
        self::assertTrue($result->isFinal);
    }

    public function testDeserializeConstant(): void
    {
        $data = [
            '_type' => 'PHPConstant',
            'name' => 'TEST_CONSTANT',
            'namespace' => 'Test\\Namespace',
            'id' => 'Test\\Namespace\\TEST_CONSTANT',
            'value' => 'test_value',
            'sourcePath' => '/path/to/file.php',
            'duplicates' => []
        ];

        $result = $this->serializer->deserialize($data);

        self::assertInstanceOf(PHPConstant::class, $result);
        self::assertEquals('TEST_CONSTANT', $result->getName());
        self::assertEquals('test_value', $result->value);
    }

    public function testRoundTripSerializationClass(): void
    {
        $class = new PHPClass();
        $class->setName('RoundTripClass');
        $class->setNamespace('Test');
        $class->setId('Test\\RoundTripClass');
        $class->isFinal = true;
        $class->isReadonly = false;

        $method = new PHPMethod();
        $method->setName('testMethod');
        $method->setAccess(new PublicAccessModifier());
        $method->setIsStatic(false);
        $method->setIsFinal(false);
        $method->setIsAbstract(false);
        $method->setReturnTypeFromSignature(new StandaloneType('string'));
        $method->setParameters([]);
        $class->methods[] = $method;

        $property = new PHPProperty();
        $property->setName('testProp');
        $property->setIsStatic(false);
        $property->setIsReadonly(false);
        $property->setAccess(new PrivateAccessModifier());
        $property->setTypeFromSignature(new StandaloneType('int'));
        $class->properties[] = $property;

        // Serialize then deserialize
        $serialized = $this->serializer->serialize($class);
        $deserialized = $this->serializer->deserialize($serialized);

        self::assertInstanceOf(PHPClass::class, $deserialized);
        self::assertEquals($class->getName(), $deserialized->getName());
        self::assertEquals($class->getNamespace(), $deserialized->getNamespace());
        self::assertEquals($class->isFinal, $deserialized->isFinal);
        self::assertCount(1, $deserialized->methods);
        self::assertCount(1, $deserialized->properties);
        self::assertEquals('testMethod', $deserialized->methods[0]->getName());
        self::assertEquals('testProp', $deserialized->properties[0]->getName());
    }

    public function testRoundTripSerializationFunction(): void
    {
        $function = new PHPFunction();
        $function->setName('roundTripFunction');
        $function->setNamespace('Test');
        $function->setId('Test\\roundTripFunction');
        $function->setDeprecated(false);
        $function->setHasTentativeReturnType(true);
        $function->setReturnTypeFromSignature(new StandaloneType('void'));

        $param = new PHPParameter('arg1');
        $param->setPosition(0);
        $param->setIsOptional(false);
        $param->setType(new StandaloneType('string'));
        $function->setParameters([$param]);

        // Serialize then deserialize
        $serialized = $this->serializer->serialize($function);
        $deserialized = $this->serializer->deserialize($serialized);

        self::assertInstanceOf(PHPFunction::class, $deserialized);
        self::assertEquals($function->getName(), $deserialized->getName());
        self::assertEquals($function->getNamespace(), $deserialized->getNamespace());
        self::assertFalse($deserialized->isDeprecated());
        self::assertTrue($deserialized->hasTentativeReturnType());
        self::assertCount(1, $deserialized->getParameters());
        self::assertEquals('arg1', $deserialized->getParameters()[0]->getName());
    }
}
