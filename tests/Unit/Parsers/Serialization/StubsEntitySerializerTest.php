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
use StubTests\Sources\Parsers\StubsEntitySerializer;

class StubsEntitySerializerTest extends TestCase
{
    private StubsEntitySerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new StubsEntitySerializer();
    }

    public function testSerializeClassWithStubMetadata(): void
    {
        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setNamespace('Test\\Namespace');
        $class->setId('Test\\Namespace\\TestClass');
        $class->isFinal = true;
        $class->isReadonly = false;
        $class->setSourcePath('/path/to/file.php');
        $class->setDuplicates([]);
        $class->setPhpDoc('/** @deprecated This class is deprecated */');
        $class->setSinceVersion('8.0');
        $class->setRemovedVersion('9.0');

        $result = $this->serializer->serialize($class);

        self::assertEquals('PHPClass', $result['_type']);
        self::assertEquals('TestClass', $result['name']);
        self::assertEquals('Test\\Namespace', $result['namespace']);
        self::assertTrue($result['isFinal']);
        self::assertEquals('/** @deprecated This class is deprecated */', $result['phpDoc']);
        self::assertEquals('8.0', $result['sinceVersion']);
        self::assertEquals('9.0', $result['removedVersion']);
        self::assertIsArray($result['methods']);
        self::assertIsArray($result['properties']);
        self::assertIsArray($result['constants']);
    }

    public function testSerializeMethodWithStubMetadata(): void
    {
        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setId('TestClass');

        $method = new PHPMethod();
        $method->setName('testMethod');
        $method->setIsStatic(false);
        $method->setIsFinal(false);
        $method->setIsAbstract(false);
        $method->setDeprecated(true);
        $method->setAccess(new PublicAccessModifier());
        $method->setParameters([]);

        $returnType = new StandaloneType('string');
        $method->setReturnTypeFromSignature($returnType);
        $method->setHasTentativeReturnType(false);

        // Stub-specific metadata
        $method->setPhpDoc('/** @return string The result */');
        $method->setSinceVersion('7.4');
        $method->setRemovedVersion(null);
        $method->setReturnTypeFromPhpDoc('string|false');
        $method->setLanguageLevelTypes(['8.0' => 'string', '7.4' => 'string|false']);
        $method->setDefaultType('string');

        $class->methods[] = $method;

        $result = $this->serializer->serialize($class);

        self::assertCount(1, $result['methods']);
        $methodData = $result['methods'][0];

        self::assertEquals('testMethod', $methodData['name']);
        self::assertTrue($methodData['isDeprecated']);
        self::assertEquals('string', $methodData['returnType']);
        self::assertEquals('/** @return string The result */', $methodData['phpDoc']);
        self::assertEquals('7.4', $methodData['sinceVersion']);
        self::assertNull($methodData['removedVersion']);
        self::assertEquals('string|false', $methodData['returnTypeFromPhpDoc']);
        self::assertIsArray($methodData['languageLevelTypes']);
        self::assertEquals('string', $methodData['defaultType']);
    }

    public function testSerializeParameterWithStubMetadata(): void
    {
        $method = new PHPMethod();
        $method->setName('testMethod');
        $method->setAccess(new PublicAccessModifier());
        $method->setIsStatic(false);
        $method->setIsFinal(false);
        $method->setIsAbstract(false);
        $method->setReturnTypeFromSignature(new NoType());

        $param = new PHPParameter('testParam');
        $param->setPosition(0);
        $param->setIsOptional(true);
        $param->setIsVariadic(false);
        $param->setIsPassedByReference(false);
        $param->setHasDefaultValue(true);
        $param->setDefaultValue(null);
        $param->setType(new StandaloneType('?string'));

        // Stub-specific metadata
        $param->setTypeFromPhpDoc('string|null');
        $param->setLanguageLevelTypes(['8.0' => '?string', '7.4' => 'string']);
        $param->setDefaultType('string');
        $param->setSinceVersion('8.0');
        $param->setRemovedVersion(null);

        $method->setParameters([$param]);

        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setId('TestClass');
        $class->methods[] = $method;

        $result = $this->serializer->serialize($class);

        $parameters = $result['methods'][0]['parameters'];
        self::assertCount(1, $parameters);

        $paramData = $parameters[0];
        self::assertEquals('testParam', $paramData['name']);
        self::assertEquals('?string', $paramData['type']);
        self::assertEquals('string|null', $paramData['typeFromPhpDoc']);
        self::assertIsArray($paramData['languageLevelTypes']);
        self::assertEquals('string', $paramData['defaultType']);
        self::assertEquals('8.0', $paramData['sinceVersion']);
        self::assertNull($paramData['removedVersion']);
    }

    public function testSerializePropertyWithStubMetadata(): void
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

        // Stub-specific metadata
        $property->setPhpDoc('/** @var int The counter */');
        $property->setSinceVersion('8.1');
        $property->setRemovedVersion(null);
        $property->setTypeFromPhpDoc('int|null');
        $property->setLanguageLevelTypes(['8.1' => 'int']);
        $property->setDefaultType('int');

        $class->properties[] = $property;

        $result = $this->serializer->serialize($class);

        self::assertCount(1, $result['properties']);
        $propertyData = $result['properties'][0];

        self::assertEquals('testProperty', $propertyData['name']);
        self::assertEquals('int', $propertyData['type']);
        self::assertEquals('/** @var int The counter */', $propertyData['phpDoc']);
        self::assertEquals('8.1', $propertyData['sinceVersion']);
        self::assertNull($propertyData['removedVersion']);
        self::assertEquals('int|null', $propertyData['typeFromPhpDoc']);
        self::assertIsArray($propertyData['languageLevelTypes']);
        self::assertEquals('int', $propertyData['defaultType']);
    }

    public function testSerializeFunctionWithStubMetadata(): void
    {
        $function = new PHPFunction();
        $function->setName('testFunction');
        $function->setNamespace('Test\\Namespace');
        $function->setId('Test\\Namespace\\testFunction');
        $function->setDeprecated(false);
        $function->setSourcePath('/path/to/file.php');
        $function->setDuplicates([]);
        $function->setHasTentativeReturnType(false);

        $returnType = new StandaloneType('bool');
        $function->setReturnTypeFromSignature($returnType);
        $function->setParameters([]);

        // Stub-specific metadata
        $function->setPhpDoc('/** @return bool Success status */');
        $function->setSinceVersion('7.0');
        $function->setRemovedVersion(null);
        $function->setReturnTypeFromPhpDoc('bool');
        $function->setLanguageLevelTypes(['8.0' => 'bool', '7.0' => 'bool']);
        $function->setDefaultType('bool');

        $result = $this->serializer->serialize($function);

        self::assertEquals('PHPFunction', $result['_type']);
        self::assertEquals('testFunction', $result['name']);
        self::assertEquals('bool', $result['returnType']);
        self::assertEquals('/** @return bool Success status */', $result['phpDoc']);
        self::assertEquals('7.0', $result['sinceVersion']);
        self::assertNull($result['removedVersion']);
        self::assertEquals('bool', $result['returnTypeFromPhpDoc']);
        self::assertIsArray($result['languageLevelTypes']);
        self::assertEquals('bool', $result['defaultType']);
    }

    public function testSerializeInterfaceWithStubMetadata(): void
    {
        $interface = new PHPInterface();
        $interface->setName('TestInterface');
        $interface->setNamespace('Test\\Namespace');
        $interface->setId('Test\\Namespace\\TestInterface');
        $interface->setSourcePath('/path/to/file.php');
        $interface->setDuplicates([]);

        // Stub-specific metadata
        $interface->setPhpDoc('/** @package Test */');
        $interface->setSinceVersion('8.0');
        $interface->setRemovedVersion(null);

        $result = $this->serializer->serialize($interface);

        self::assertEquals('PHPInterface', $result['_type']);
        self::assertEquals('TestInterface', $result['name']);
        self::assertEquals('/** @package Test */', $result['phpDoc']);
        self::assertEquals('8.0', $result['sinceVersion']);
        self::assertNull($result['removedVersion']);
    }

    public function testSerializeEnumWithStubMetadata(): void
    {
        $enum = new PHPEnum();
        $enum->setName('TestEnum');
        $enum->setNamespace('Test\\Namespace');
        $enum->setId('Test\\Namespace\\TestEnum');
        $enum->isFinal = true;
        $enum->isReadonly = false;
        $enum->setSourcePath('/path/to/file.php');
        $enum->setDuplicates([]);

        // Stub-specific metadata
        $enum->setPhpDoc('/** @package Test */');
        $enum->setSinceVersion('8.1');
        $enum->setRemovedVersion(null);

        $result = $this->serializer->serialize($enum);

        self::assertEquals('PHPEnum', $result['_type']);
        self::assertEquals('TestEnum', $result['name']);
        self::assertEquals('/** @package Test */', $result['phpDoc']);
        self::assertEquals('8.1', $result['sinceVersion']);
        self::assertNull($result['removedVersion']);
    }

    public function testSerializeConstantWithStubMetadata(): void
    {
        $constant = new PHPConstant();
        $constant->setName('TEST_CONSTANT');
        $constant->setNamespace('Test\\Namespace');
        $constant->setId('Test\\Namespace\\TEST_CONSTANT');
        $constant->value = 42;
        $constant->setSourcePath('/path/to/file.php');
        $constant->setDuplicates([]);

        // Stub-specific metadata
        $constant->setPhpDoc('/** @var int */');
        $constant->setSinceVersion('7.0');
        $constant->setRemovedVersion('8.0');

        $result = $this->serializer->serialize($constant);

        self::assertEquals('PHPConstant', $result['_type']);
        self::assertEquals('TEST_CONSTANT', $result['name']);
        self::assertEquals(42, $result['value']);
        self::assertEquals('/** @var int */', $result['phpDoc']);
        self::assertEquals('7.0', $result['sinceVersion']);
        self::assertEquals('8.0', $result['removedVersion']);
    }

    public function testDeserializeClassWithStubMetadata(): void
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
            'phpDoc' => '/** @deprecated */',
            'sinceVersion' => '8.0',
            'removedVersion' => null,
            'methods' => [],
            'properties' => [],
            'constants' => []
        ];

        $result = $this->serializer->deserialize($data);

        self::assertInstanceOf(PHPClass::class, $result);
        self::assertEquals('TestClass', $result->getName());
        self::assertEquals('Test\\Namespace', $result->getNamespace());
        self::assertEquals('/** @deprecated */', $result->getPhpDoc());
        self::assertEquals('8.0', $result->getSinceVersion());
        self::assertNull($result->getRemovedVersion());
    }

    public function testDeserializeMethodWithStubMetadata(): void
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
                    'isDeprecated' => true,
                    'accessModifier' => 'public',
                    'parameters' => [],
                    'returnType' => 'string',
                    'hasTentativeReturnType' => false,
                    'phpDoc' => '/** @return string */',
                    'sinceVersion' => '7.4',
                    'removedVersion' => null,
                    'returnTypeFromPhpDoc' => 'string|false',
                    'languageLevelTypes' => ['8.0' => 'string'],
                    'defaultType' => 'string'
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
        self::assertTrue($method->isDeprecated());
        self::assertEquals('/** @return string */', $method->getPhpDoc());
        self::assertEquals('7.4', $method->getSinceVersion());
        self::assertNull($method->getRemovedVersion());
        self::assertEquals('string|false', $method->getReturnTypeFromPhpDoc());
        self::assertIsArray($method->getLanguageLevelTypes());
        self::assertEquals('string', $method->getDefaultType());
    }

    public function testDeserializeParameterWithStubMetadata(): void
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
                            'type' => '?string',
                            'defaultValue' => null,
                            'typeFromPhpDoc' => 'string|null',
                            'languageLevelTypes' => ['8.0' => '?string'],
                            'defaultType' => 'string',
                            'sinceVersion' => '8.0',
                            'removedVersion' => null
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
        self::assertEquals('string|null', $param->getTypeFromPhpDoc());
        self::assertIsArray($param->getLanguageLevelTypes());
        self::assertEquals('string', $param->getDefaultType());
        self::assertEquals('8.0', $param->getSinceVersion());
        self::assertNull($param->getRemovedVersion());
    }

    public function testDeserializePropertyWithStubMetadata(): void
    {
        $data = [
            '_type' => 'PHPClass',
            'name' => 'TestClass',
            'id' => 'TestClass',
            'methods' => [],
            'properties' => [
                [
                    'name' => 'testProperty',
                    'isStatic' => false,
                    'isReadonly' => true,
                    'accessModifier' => 'protected',
                    'type' => 'int',
                    'phpDoc' => '/** @var int */',
                    'sinceVersion' => '8.1',
                    'removedVersion' => null,
                    'typeFromPhpDoc' => 'int|null',
                    'languageLevelTypes' => ['8.1' => 'int'],
                    'defaultType' => 'int'
                ]
            ],
            'constants' => []
        ];

        $result = $this->serializer->deserialize($data);

        self::assertCount(1, $result->properties);

        $property = $result->properties[0];
        self::assertEquals('testProperty', $property->getName());
        self::assertEquals('/** @var int */', $property->getPhpDoc());
        self::assertEquals('8.1', $property->getSinceVersion());
        self::assertNull($property->getRemovedVersion());
        self::assertEquals('int|null', $property->getTypeFromPhpDoc());
        self::assertIsArray($property->getLanguageLevelTypes());
        self::assertEquals('int', $property->getDefaultType());
    }

    public function testDeserializeFunctionWithStubMetadata(): void
    {
        $data = [
            '_type' => 'PHPFunction',
            'name' => 'testFunction',
            'namespace' => 'Test\\Namespace',
            'id' => 'Test\\Namespace\\testFunction',
            'isDeprecated' => false,
            'returnType' => 'bool',
            'sourcePath' => '/path/to/file.php',
            'duplicates' => [],
            'hasTentativeReturnType' => false,
            'parameters' => [],
            'phpDoc' => '/** @return bool */',
            'sinceVersion' => '7.0',
            'removedVersion' => null,
            'returnTypeFromPhpDoc' => 'bool',
            'languageLevelTypes' => ['8.0' => 'bool'],
            'defaultType' => 'bool'
        ];

        $result = $this->serializer->deserialize($data);

        self::assertInstanceOf(PHPFunction::class, $result);
        self::assertEquals('testFunction', $result->getName());
        self::assertEquals('/** @return bool */', $result->getPhpDoc());
        self::assertEquals('7.0', $result->getSinceVersion());
        self::assertNull($result->getRemovedVersion());
        self::assertEquals('bool', $result->getReturnTypeFromPhpDoc());
        self::assertIsArray($result->getLanguageLevelTypes());
        self::assertEquals('bool', $result->getDefaultType());
    }

    public function testDeserializeConstantWithStubMetadata(): void
    {
        $data = [
            '_type' => 'PHPConstant',
            'name' => 'TEST_CONSTANT',
            'namespace' => 'Test\\Namespace',
            'id' => 'Test\\Namespace\\TEST_CONSTANT',
            'value' => 42,
            'sourcePath' => '/path/to/file.php',
            'duplicates' => [],
            'phpDoc' => '/** @var int */',
            'sinceVersion' => '7.0',
            'removedVersion' => '8.0'
        ];

        $result = $this->serializer->deserialize($data);

        self::assertInstanceOf(PHPConstant::class, $result);
        self::assertEquals('TEST_CONSTANT', $result->getName());
        self::assertEquals('/** @var int */', $result->getPhpDoc());
        self::assertEquals('7.0', $result->getSinceVersion());
        self::assertEquals('8.0', $result->getRemovedVersion());
    }

    public function testRoundTripSerializationClassWithStubMetadata(): void
    {
        $class = new PHPClass();
        $class->setName('RoundTripClass');
        $class->setNamespace('Test');
        $class->setId('Test\\RoundTripClass');
        $class->isFinal = true;
        $class->isReadonly = false;
        $class->setPhpDoc('/** @since 8.0 */');
        $class->setSinceVersion('8.0');
        $class->setRemovedVersion(null);

        $method = new PHPMethod();
        $method->setName('testMethod');
        $method->setAccess(new PublicAccessModifier());
        $method->setIsStatic(false);
        $method->setIsFinal(false);
        $method->setIsAbstract(false);
        $method->setReturnTypeFromSignature(new StandaloneType('string'));
        $method->setParameters([]);
        $method->setPhpDoc('/** @return string */');
        $method->setSinceVersion('8.0');
        $class->methods[] = $method;

        // Serialize then deserialize
        $serialized = $this->serializer->serialize($class);
        $deserialized = $this->serializer->deserialize($serialized);

        self::assertInstanceOf(PHPClass::class, $deserialized);
        self::assertEquals($class->getName(), $deserialized->getName());
        self::assertEquals($class->getPhpDoc(), $deserialized->getPhpDoc());
        self::assertEquals($class->getSinceVersion(), $deserialized->getSinceVersion());
        self::assertCount(1, $deserialized->methods);
        self::assertEquals('testMethod', $deserialized->methods[0]->getName());
        self::assertEquals('/** @return string */', $deserialized->methods[0]->getPhpDoc());
        self::assertEquals('8.0', $deserialized->methods[0]->getSinceVersion());
    }

    public function testRoundTripSerializationFunctionWithStubMetadata(): void
    {
        $function = new PHPFunction();
        $function->setName('roundTripFunction');
        $function->setNamespace('Test');
        $function->setId('Test\\roundTripFunction');
        $function->setDeprecated(false);
        $function->setHasTentativeReturnType(false);
        $function->setReturnTypeFromSignature(new StandaloneType('void'));
        $function->setPhpDoc('/** @return void */');
        $function->setSinceVersion('7.4');
        $function->setRemovedVersion(null);
        $function->setReturnTypeFromPhpDoc('void');
        $function->setLanguageLevelTypes(['8.0' => 'void']);
        $function->setDefaultType('void');

        $param = new PHPParameter('arg1');
        $param->setPosition(0);
        $param->setIsOptional(false);
        $param->setType(new StandaloneType('string'));
        $param->setTypeFromPhpDoc('string');
        $param->setSinceVersion('7.4');
        $function->setParameters([$param]);

        // Serialize then deserialize
        $serialized = $this->serializer->serialize($function);
        $deserialized = $this->serializer->deserialize($serialized);

        self::assertInstanceOf(PHPFunction::class, $deserialized);
        self::assertEquals($function->getName(), $deserialized->getName());
        self::assertEquals($function->getPhpDoc(), $deserialized->getPhpDoc());
        self::assertEquals($function->getSinceVersion(), $deserialized->getSinceVersion());
        self::assertEquals($function->getReturnTypeFromPhpDoc(), $deserialized->getReturnTypeFromPhpDoc());
        self::assertCount(1, $deserialized->getParameters());
        self::assertEquals('arg1', $deserialized->getParameters()[0]->getName());
        self::assertEquals('string', $deserialized->getParameters()[0]->getTypeFromPhpDoc());
        self::assertEquals('7.4', $deserialized->getParameters()[0]->getSinceVersion());
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
}
