<?php

namespace StubTests\Sources\Runner;

use ReflectionClass;
use StubTests\Sources\DataProvider\AllStubsDataProvider;
use StubTests\Sources\DataProvider\CurrentRuntimeReflectionRawDataProvider;
use StubTests\Sources\Parsers\Entities\Reflection\AllReflectionParser;
use StubTests\Sources\Parsers\Entities\Stubs\AllStubsParser;
use StubTests\Sources\Parsers\Entities\Stubs\StubClassParser;
use StubTests\Sources\Parsers\Entities\Stubs\StubFunctionParser;
use StubTests\Sources\Parsers\DefaultParsedDataStorageManager;
use StubTests\Sources\Parsers\InMemoryParsedDataStorage;
use StubTests\Sources\Parsers\JsonParsedDataStorage;
use StubTests\Sources\Parsers\MultiFileJsonStorage;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Parsers\PhpDocStorage;
use StubTests\Sources\Parsers\ReflectionEntitySerializer;
use StubTests\Sources\Parsers\StubsEntitySerializer;

class Runner
{

    public static function getReflection(string $phpVersion): ParsedDataStorageManager
    {
        $cacheFilePath = __DIR__ . "/../../cache/Reflection$phpVersion.json";
        $serializer = new ReflectionEntitySerializer();

        if (file_exists($cacheFilePath)) {
            // Load from cache
            return new DefaultParsedDataStorageManager(
                new JsonParsedDataStorage($cacheFilePath, $serializer, true)
            );
        } else {
            // Parse reflection data and save to cache
            $parsedReflectionDataStorageManager = new DefaultParsedDataStorageManager(
                new JsonParsedDataStorage($cacheFilePath, $serializer, false)
            );
            $parser = new AllReflectionParser(
                new CurrentRuntimeReflectionRawDataProvider(),
                $parsedReflectionDataStorageManager
            );
            $parser->parseAll();

            // Save parsed data to JSON cache for future use
            $parsedReflectionDataStorageManager->save();

            return $parsedReflectionDataStorageManager;
        }
    }

    public static function getStubs(): ParsedDataStorageManager
    {
        $cacheFilePath = __DIR__ . "/../../cache/Stubs.json";
        $phpDocCacheFilePath = __DIR__ . "/../../cache/StubsPhpDoc.json";

        // Check if any of the multi-file cache files exist
        $classesFile = __DIR__ . "/../../cache/StubsClasses.json";
        $cacheExists = file_exists($classesFile);

        if ($cacheExists) {
            // Load from multi-file cache with PhpDoc storage
            $phpDocStorage = new PhpDocStorage($phpDocCacheFilePath);
            $serializer = new StubsEntitySerializer($phpDocStorage);
            $storage = new MultiFileJsonStorage($cacheFilePath, $serializer, true, $phpDocStorage);
            return new DefaultParsedDataStorageManager($storage);
        } else {
            // Parse stubs and save to cache
            $phpDocStorage = new PhpDocStorage($phpDocCacheFilePath, false);
            $serializer = new StubsEntitySerializer($phpDocStorage);
            $storage = new MultiFileJsonStorage($cacheFilePath, $serializer, false, $phpDocStorage);
            $parsedStubsDataStorageManager = new DefaultParsedDataStorageManager($storage);
            $parser = new AllStubsParser(
                new AllStubsDataProvider(),
                $parsedStubsDataStorageManager,
                [new StubClassParser(), new StubFunctionParser()]
            );
            $parser->parseAll();

            // Save parsed data to JSON cache for future use
            $parsedStubsDataStorageManager->save();

            return $parsedStubsDataStorageManager;
        }
    }
}