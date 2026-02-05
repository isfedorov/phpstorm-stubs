#!/usr/bin/env php
<?php

/**
 * Standalone CLI script to parse all PHP reflection data and write to JSON cache.
 *
 * Usage:
 *   php tests/run-reflection-parser.php [php-version]
 *
 * Example:
 *   php tests/run-reflection-parser.php 8.3
 *   php tests/run-reflection-parser.php  # Uses current PHP version
 */

// Suppress deprecation warnings to clean up output
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

use StubTests\Sources\DataProvider\CurrentRuntimeReflectionDataProvider;
use StubTests\Sources\Parsers\DefaultParsedDataStorageManager;
use StubTests\Sources\Parsers\Entities\Reflection\AllReflectionParser;
use StubTests\Sources\Parsers\JsonParsedDataStorage;

// Parse CLI arguments
$phpVersion = $argv[1] ?? PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

echo "========================================\n";
echo "PHP Reflection Parser Runner\n";
echo "========================================\n";
echo "PHP Version: {$phpVersion}\n";
echo "Runtime Version: " . PHP_VERSION . "\n";
echo "Start Time: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

// Setup paths
$cacheFilePath = __DIR__ . "/cache/Reflection{$phpVersion}.json";

echo "Output File: {$cacheFilePath}\n\n";

try {
    // Create data provider
    echo "[1/4] Creating reflection data provider...\n";
    $dataProvider = new CurrentRuntimeReflectionDataProvider();
    echo "      ✓ Data provider created\n\n";

    // Create storage manager with JSON storage
    echo "[2/4] Creating storage manager...\n";
    $storage = new JsonParsedDataStorage($cacheFilePath);
    $storageManager = new DefaultParsedDataStorageManager($storage);
    echo "      ✓ Storage manager created\n\n";

    // Create and run parser
    echo "[3/4] Parsing all reflection entities...\n";
    $parser = new AllReflectionParser($dataProvider, $storageManager);

    echo "      - Parsing classes...\n";
    $startTime = microtime(true);
    $parser->parseAll();
    $parseTime = microtime(true) - $startTime;

    echo "      ✓ Parsing completed in " . number_format($parseTime, 2) . " seconds\n\n";

    // Save to JSON
    echo "[4/4] Saving to JSON file...\n";
    $entitiesBeforeSave = $storageManager->getAllEntities();
    echo "      - Entities to save: " . count($entitiesBeforeSave) . "\n";

    $startTime = microtime(true);
    $storageManager->save();
    $saveTime = microtime(true) - $startTime;

    echo "      ✓ Saved in " . number_format($saveTime, 2) . " seconds\n";

    // Verify file was written
    if (file_exists($cacheFilePath)) {
        $fileSize = filesize($cacheFilePath);
        echo "      ✓ File created with size: " . number_format($fileSize) . " bytes\n";
    } else {
        echo "      ✗ File was not created!\n";
    }
    echo "\n";

    // Display statistics
    $allEntities = $storageManager->getAllEntities();
    $classes = $storageManager->getClasses();
    $functions = $storageManager->getFunctions();
    $interfaces = $storageManager->getInterfaces();
    $enums = $storageManager->getEnums();
    $constants = $storageManager->getConstants();

    echo "========================================\n";
    echo "Statistics:\n";
    echo "========================================\n";
    echo "Total Entities: " . count($allEntities) . "\n";
    echo "  - Classes:    " . count($classes) . "\n";
    echo "  - Functions:  " . count($functions) . "\n";
    echo "  - Interfaces: " . count($interfaces) . "\n";
    echo "  - Enums:      " . count($enums) . "\n";
    echo "  - Constants:  " . count($constants) . "\n";
    echo "========================================\n";

    $fileSize = filesize($cacheFilePath);
    $fileSizeFormatted = number_format($fileSize / 1024 / 1024, 2);
    echo "Output File Size: {$fileSizeFormatted} MB\n";
    echo "========================================\n\n";

    echo "✓ SUCCESS: Reflection parsing completed successfully!\n";
    echo "          Output saved to: {$cacheFilePath}\n\n";

    // Show sample data
    echo "Sample Entities:\n";
    echo "----------------------------------------\n";

    if (count($classes) > 0) {
        $sampleClass = reset($classes);
        echo "  Class: " . $sampleClass->getId() . "\n";
    }

    if (count($functions) > 0) {
        $sampleFunction = reset($functions);
        echo "  Function: " . $sampleFunction->getId() . "\n";
    }

    if (count($interfaces) > 0) {
        $sampleInterface = reset($interfaces);
        echo "  Interface: " . $sampleInterface->getId() . "\n";
    }

    if (count($enums) > 0) {
        $sampleEnum = reset($enums);
        echo "  Enum: " . $sampleEnum->getId() . "\n";
    }

    if (count($constants) > 0) {
        $sampleConstant = reset($constants);
        echo "  Constant: " . $sampleConstant->getId() . "\n";
    }

    echo "========================================\n\n";

    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
