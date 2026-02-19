<?php

namespace StubTests\Unit\Parsers\Serialization;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\PhpDocStorage;
use StubTests\Sources\Parsers\StubsEntitySerializer;

class PhpDocSeparationTest extends TestCase
{
    private string $testFilePath;
    private PhpDocStorage $phpDocStorage;
    private StubsEntitySerializer $serializer;

    protected function setUp(): void
    {
        $this->testFilePath = sys_get_temp_dir() . '/phpstorm-stubs-test-phpdoc-' . uniqid() . '.json';
        $this->phpDocStorage = new PhpDocStorage($this->testFilePath, false);
        $this->serializer = new StubsEntitySerializer($this->phpDocStorage);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
    }

    public function testClassPhpDocIsSeparated(): void
    {
        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setId('\\TestClass');
        $class->setPhpDoc('/** @deprecated This is a test class */');

        $serialized = $this->serializer->serialize($class);

        // PhpDoc should be null in serialized data (stored externally)
        self::assertNull($serialized['phpDoc']);

        // PhpDoc should be in external storage
        self::assertEquals('/** @deprecated This is a test class */', $this->phpDocStorage->getPhpDoc('\\TestClass'));
    }

    public function testFunctionPhpDocIsSeparated(): void
    {
        $function = new PHPFunction();
        $function->setName('testFunction');
        $function->setId('\\testFunction');
        $function->setPhpDoc('/** @return bool */');

        $serialized = $this->serializer->serialize($function);

        // PhpDoc should be null in serialized data
        self::assertNull($serialized['phpDoc']);

        // PhpDoc should be in external storage
        self::assertEquals('/** @return bool */', $this->phpDocStorage->getPhpDoc('\\testFunction'));
    }

    public function testMethodPhpDocIsSeparated(): void
    {
        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setId('\\TestClass');

        $method = new PHPMethod();
        $method->setName('testMethod');
        $method->setIsStatic(false);
        $method->setIsFinal(false);
        $method->setIsAbstract(false);
        $method->setDeprecated(false);
        $method->setParameters([]);
        $method->setPhpDoc('/** @return string */');

        $class->methods[] = $method;

        $serialized = $this->serializer->serialize($class);

        // Method PhpDoc should be null in serialized data
        self::assertNull($serialized['methods'][0]['phpDoc']);

        // Method PhpDoc should be in external storage with class::method key
        self::assertEquals('/** @return string */', $this->phpDocStorage->getPhpDoc('\\TestClass::testMethod'));
    }

    public function testPropertyPhpDocIsSeparated(): void
    {
        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setId('\\TestClass');

        $property = new PHPProperty();
        $property->setName('testProperty');
        $property->setPhpDoc('/** @var int */');

        $class->properties[] = $property;

        $serialized = $this->serializer->serialize($class);

        // Property PhpDoc should be null in serialized data
        self::assertNull($serialized['properties'][0]['phpDoc']);

        // Property PhpDoc should be in external storage with class::$property key
        self::assertEquals('/** @var int */', $this->phpDocStorage->getPhpDoc('\\TestClass::$testProperty'));
    }

    public function testDeserializationLoadsPhpDocFromStorage(): void
    {
        // Serialize a class with PhpDoc
        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setId('\\TestClass');
        $class->setPhpDoc('/** @since 8.0 */');

        $serialized = $this->serializer->serialize($class);
        $this->phpDocStorage->save();

        // Create new storage and serializer to simulate loading
        $newPhpDocStorage = new PhpDocStorage($this->testFilePath);
        $newSerializer = new StubsEntitySerializer($newPhpDocStorage);

        // Deserialize
        $deserialized = $newSerializer->deserialize($serialized);

        // PhpDoc should be loaded from external storage
        self::assertEquals('/** @since 8.0 */', $deserialized->getPhpDoc());
    }

    public function testWithoutPhpDocStoragePhpDocIsInline(): void
    {
        // Create serializer without PhpDocStorage
        $inlineSerializer = new StubsEntitySerializer(null);

        $class = new PHPClass();
        $class->setName('TestClass');
        $class->setId('\\TestClass');
        $class->setPhpDoc('/** @deprecated */');

        $serialized = $inlineSerializer->serialize($class);

        // PhpDoc should be inline (not null)
        self::assertEquals('/** @deprecated */', $serialized['phpDoc']);

        // Deserialize should work
        $deserialized = $inlineSerializer->deserialize($serialized);
        self::assertEquals('/** @deprecated */', $deserialized->getPhpDoc());
    }
}
