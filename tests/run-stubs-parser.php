#!/usr/bin/env php
<?php

/**
 * Standalone CLI script to parse all PHP stubs and write to JSON cache.
 *
 * Usage:
 *   php tests/run-stubs-parser.php
 *
 * Note: Stubs are version-agnostic and represent a unified view across all PHP versions.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use StubTests\Framework\Parsers\Processors\EntityProcessingPipeline;
use StubTests\Sources\DataProvider\AllStubsDataProvider;
use StubTests\Sources\Parsers\DefaultParsedDataStorageManager;
use StubTests\Sources\Parsers\Entities\Stubs\AllStubsParser;
use StubTests\Sources\Parsers\Entities\Stubs\StubClassParser;
use StubTests\Sources\Parsers\Entities\Stubs\StubDefineConstantParser;
use StubTests\Sources\Parsers\Entities\Stubs\StubEnumParser;
use StubTests\Sources\Parsers\Entities\Stubs\StubFunctionParser;
use StubTests\Sources\Parsers\Entities\Stubs\StubInterfaceParser;
use StubTests\Sources\Parsers\Entities\Stubs\StubModernConstantParser;
use StubTests\Sources\Parsers\JsonParsedDataStorage;
use StubTests\Sources\Parsers\PhpDocStorage;
use StubTests\Sources\Parsers\Processors\StubsDeduplicationProcessor;
use StubTests\Sources\Parsers\StubsEntitySerializer;

echo "========================================\n";
echo "PHP Stubs Parser Runner\n";
echo "========================================\n";
echo "Start Time: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

// Setup paths
$stubsRootPath = dirname(__DIR__);
$cacheFilePath = __DIR__ . "/cache/Stubs.json";
$phpDocCacheFilePath = __DIR__ . "/cache/StubsPhpDoc.json";

echo "Stubs Root: {$stubsRootPath}\n";
echo "Output File: {$cacheFilePath}\n";
echo "PhpDoc File: {$phpDocCacheFilePath}\n\n";

try {
    // Create data provider
    echo "[1/5] Creating data provider...\n";
    $dataProvider = new AllStubsDataProvider($stubsRootPath);
    echo "      ✓ Data provider created\n\n";

    // Create storage manager with JSON storage and deduplication pipeline
    echo "[2/5] Creating storage manager with separate PhpDoc storage and deduplication pipeline...\n";
    $phpDocStorage = new PhpDocStorage($phpDocCacheFilePath, false); // Start fresh
    $serializer = new StubsEntitySerializer($phpDocStorage);
    $storage = new JsonParsedDataStorage($cacheFilePath, $serializer, false, $phpDocStorage); // Start fresh, don't load existing
    $pipeline = new EntityProcessingPipeline();
    $pipeline->addProcessor(new StubsDeduplicationProcessor());
    $storageManager = new DefaultParsedDataStorageManager($storage, $pipeline);
    echo "      ✓ Storage manager created with separate PhpDoc storage and deduplication enabled\n\n";

    // Create parsers
    echo "[3/5] Creating parsers...\n";
    $parsers = [
        new StubClassParser(),
        new StubFunctionParser(),
        new StubInterfaceParser(),
        new StubEnumParser(),
        new StubDefineConstantParser(),
        new StubModernConstantParser(),
    ];
    echo "      ✓ " . count($parsers) . " parsers ready (Classes, Functions, Interfaces, Enums, Constants)\n\n";

    // Create and run parser
    echo "[4/5] Parsing all stub files...\n";
    $parser = new AllStubsParser($dataProvider, $storageManager, $parsers);

    $startTime = microtime(true);
    $parser->parseAll();
    $parseTime = microtime(true) - $startTime;

    echo "      ✓ Parsing completed in " . number_format($parseTime, 2) . " seconds\n\n";

    // Save to JSON
    echo "[5/5] Saving to JSON file...\n";
    $startTime = microtime(true);
    $storageManager->save();
    $saveTime = microtime(true) - $startTime;

    echo "      ✓ Saved in " . number_format($saveTime, 2) . " seconds\n\n";

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
    $phpDocFileSize = filesize($phpDocCacheFilePath);
    $phpDocFileSizeFormatted = number_format($phpDocFileSize / 1024 / 1024, 2);
    echo "Output File Size:     {$fileSizeFormatted} MB\n";
    echo "PhpDoc File Size:     {$phpDocFileSizeFormatted} MB\n";
    echo "Total Size:           " . number_format(($fileSize + $phpDocFileSize) / 1024 / 1024, 2) . " MB\n";
    echo "========================================\n\n";

    echo "✓ SUCCESS: Parsing completed successfully!\n";
    echo "          Stubs output saved to: {$cacheFilePath}\n";
    echo "          PhpDoc output saved to: {$phpDocCacheFilePath}\n\n";

    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
