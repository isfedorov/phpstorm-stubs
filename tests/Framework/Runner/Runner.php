<?php

namespace StubTests\Sources\Runner;

use StubTests\Sources\Parsers\Serializers\ReflectionEntitySerializer;
use StubTests\Sources\Parsers\Serializers\StubsEntitySerializer;
use StubTests\Sources\DataProvider\AllStubsDataProvider;
use StubTests\Sources\DataProvider\CurrentRuntimeReflectionRawDataProvider;
use StubTests\Sources\Parsers\DefaultParsedDataStorageManager;
use StubTests\Sources\Parsers\Entities\Reflection\AllReflectionParser;
use StubTests\Sources\Parsers\Entities\Stubs\AllStubsParser;
use StubTests\Sources\Parsers\Entities\Stubs\StubClassParser;
use StubTests\Sources\Parsers\Entities\Stubs\StubFunctionParser;
use StubTests\Sources\Parsers\JsonParsedDataStorage;
use StubTests\Sources\Parsers\MultiFileJsonStorage;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Parsers\PhpDocStorage;

class Runner
{
    /**
     * In-memory cache for reflection data by PHP version.
     * Prevents repeated loading/parsing of the same reflection data during test runs.
     * @var array<string, ParsedDataStorageManager>
     */
    private static array $reflectionCache = [];

    /**
     * In-memory cache for stubs data.
     * Prevents repeated loading/parsing of stubs during test runs.
     * @var ParsedDataStorageManager|null
     */
    private static ?ParsedDataStorageManager $stubsCache = null;

    public static function getReflection(string $phpVersion): ParsedDataStorageManager
    {
        // Return from in-memory cache if already loaded
        if (isset(self::$reflectionCache[$phpVersion])) {
            return self::$reflectionCache[$phpVersion];
        }

        $cacheFilePath = __DIR__ . "/../../cache/Reflection$phpVersion.json";
        $serializer = new ReflectionEntitySerializer();

        if (file_exists($cacheFilePath)) {
            // Load from cache
            $manager = new DefaultParsedDataStorageManager(
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

            $manager = $parsedReflectionDataStorageManager;
        }

        // Store in in-memory cache
        self::$reflectionCache[$phpVersion] = $manager;

        return $manager;
    }

    public static function getStubs(): ParsedDataStorageManager
    {
        // Return from in-memory cache if already loaded
        if (self::$stubsCache !== null) {
            return self::$stubsCache;
        }

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
            $manager = new DefaultParsedDataStorageManager($storage);
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

            $manager = $parsedStubsDataStorageManager;
        }

        // Store in in-memory cache
        self::$stubsCache = $manager;

        return $manager;
    }
}