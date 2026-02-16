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
use StubTests\Sources\Parsers\ParsedDataStorageManager;

class Runner
{

    public static function getReflection(string $phpVersion): ParsedDataStorageManager
    {
        $cacheFilePath = __DIR__ . "/../../cache/Reflection$phpVersion.json";

        if (file_exists($cacheFilePath)) {
            // Load from cache
            return new DefaultParsedDataStorageManager(new JsonParsedDataStorage($cacheFilePath));
        } else {
            // Parse reflection data and save to cache
            $parsedReflectionDataStorageManager = new DefaultParsedDataStorageManager(
                new JsonParsedDataStorage($cacheFilePath)
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

        if (file_exists($cacheFilePath)) {
            // Load from cache
            return new DefaultParsedDataStorageManager(new JsonParsedDataStorage($cacheFilePath));
        } else {
            // Parse stubs and save to cache
            $parsedStubsDataStorageManager = new DefaultParsedDataStorageManager(
                new JsonParsedDataStorage($cacheFilePath)
            );
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