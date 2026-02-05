#!/usr/bin/env php
<?php

/**
 * Demonstration of generic reflection data extraction
 *
 * This script demonstrates how the new generic approach automatically extracts
 * all methods from reflection objects without manual configuration.
 */

require_once __DIR__ . '/testSrc/DataProvider/Wrappers/ReflectionMethodExtractor.php';
require_once __DIR__ . '/testSrc/DataProvider/Wrappers/AbstractSerializableReflection.php';
require_once __DIR__ . '/testSrc/DataProvider/Wrappers/SerializableReflectionClass.php';
require_once __DIR__ . '/testSrc/DataProvider/Wrappers/SerializableReflectionClassReference.php';
require_once __DIR__ . '/testSrc/DataProvider/Wrappers/SerializableReflectionMethod.php';
require_once __DIR__ . '/testSrc/DataProvider/Wrappers/SerializableReflectionProperty.php';
require_once __DIR__ . '/testSrc/DataProvider/Wrappers/SerializableReflectionClassConstant.php';
require_once __DIR__ . '/testSrc/DataProvider/Wrappers/SerializableReflectionFunction.php';
require_once __DIR__ . '/testSrc/DataProvider/Wrappers/SerializableReflectionParameter.php';
require_once __DIR__ . '/testSrc/DataProvider/Wrappers/SerializableReflectionType.php';
require_once __DIR__ . '/testSrc/DataProvider/Wrappers/SerializableReflectionNamedType.php';

use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\SerializableReflectionClass;

echo "==========================================\n";
echo "Generic Reflection Data Extraction Demo\n";
echo "==========================================\n\n";

// Create a test class to demonstrate
class DemoClass {
    public const CONSTANT_VALUE = 42;

    private string $property;

    public function __construct() {}

    public function testMethod(string $param1, int $param2 = 10): void {}

    final public function finalMethod(): bool {
        return true;
    }
}

// Create reflection and wrap it
echo "1. Creating SerializableReflectionClass for DemoClass...\n";
$reflectionClass = new ReflectionClass(DemoClass::class);
$serializable = new SerializableReflectionClass($reflectionClass);

echo "   ✓ Created successfully\n\n";

// Show all automatically extracted data
echo "2. Automatically extracted methods from ReflectionClass:\n\n";
$extractedData = $serializable->getExtractedData();
foreach ($extractedData as $methodName => $value) {
    if (is_bool($value)) {
        $displayValue = $value ? 'true' : 'false';
    } elseif (is_array($value)) {
        $displayValue = '[Array with ' . count($value) . ' items]';
    } elseif (is_object($value)) {
        $displayValue = '[Object: ' . get_class($value) . ']';
    } elseif (is_null($value)) {
        $displayValue = 'null';
    } else {
        $displayValue = (string)$value;
    }

    echo "   - {$methodName}() = {$displayValue}\n";
}

// Demonstrate that new methods would be automatically included
echo "\n3. Testing access to extracted data:\n\n";
echo "   Name: " . $serializable->getName() . "\n";
echo "   Is Final: " . ($serializable->isFinal() ? 'yes' : 'no') . "\n";
echo "   Is Abstract: " . ($serializable->isAbstract() ? 'yes' : 'no') . "\n";
echo "   Is Readonly: " . ($serializable->isReadOnly() ? 'yes' : 'no') . "\n";
echo "   Number of Methods: " . count($serializable->getMethods()) . "\n";

// Test method extraction
echo "\n4. Testing SerializableReflectionMethod extraction:\n\n";
$methods = $serializable->getMethods();
if (count($methods) > 0) {
    $method = $methods[0];
    echo "   First method: " . $method->getName() . "\n";
    echo "   Extracted data keys:\n";
    foreach (array_keys($method->getExtractedData()) as $key) {
        echo "      - {$key}\n";
    }
}

// Test serialization
echo "\n5. Testing serialization/deserialization:\n\n";
$serialized = serialize($serializable);
echo "   Serialized size: " . strlen($serialized) . " bytes\n";

$deserialized = unserialize($serialized);
echo "   Deserialized successfully!\n";
echo "   Deserialized name: " . $deserialized->getName() . "\n";
echo "   Deserialized methods count: " . count($deserialized->getMethods()) . "\n";

echo "\n==========================================\n";
echo "✓ Demo completed successfully!\n";
echo "==========================================\n\n";

echo "Key Benefits:\n";
echo "  • Automatic extraction of all getter methods\n";
echo "  • Forward compatible with new PHP Reflection API methods\n";
echo "  • No manual updates needed when PHP adds new reflection features\n";
echo "  • Extendable with custom extraction logic when needed\n\n";
