<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use StubTests\Sources\DataProvider\CurrentRuntimeReflectionRawDataProvider;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Parsers\Entities\EntityType;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionClass;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionFunction;
use StubTests\Sources\Parsers\Registries\EntityReflectionObjectParsersRegistry;

/**
 * Parses all PHP reflection entities (classes, interfaces, functions, enums, constants)
 * from the current runtime and stores them in the storage manager.
 */
class AllReflectionParser
{
    private CurrentRuntimeReflectionRawDataProvider $dataProvider;
    private ParsedDataStorageManager $storageManager;
    private EntityReflectionObjectParsersRegistry $parsersRegistry;

    public function __construct(
        CurrentRuntimeReflectionRawDataProvider $dataProvider,
        ParsedDataStorageManager                $storageManager,
        ?EntityReflectionObjectParsersRegistry  $parsersRegistry = null
    ) {
        $this->dataProvider = $dataProvider;
        $this->storageManager = $storageManager;
        $this->parsersRegistry = $parsersRegistry ?? new EntityReflectionObjectParsersRegistry();
    }

    /**
     * Parse all reflection entities and store them.
     */
    public function parseAll(): void
    {
        // Parse class-like entities (classes, interfaces, enums)
        $this->parseEntities(
            array_merge(
                $this->dataProvider->getReflectionClasses(),
                $this->dataProvider->getReflectionInterfaces(),
                $this->dataProvider->getReflectionEnums()
            ),
            function($name) {
                return new AdaptedReflectionClass(new \ReflectionClass($name));
            }
        );

        // Parse functions
        $this->parseEntities(
            $this->dataProvider->getReflectionFunctions(),
            function($name) {
                return new AdaptedReflectionFunction(new \ReflectionFunction($name));
            }
        );

        // Parse constants
        $this->parseConstants();
    }

    /**
     * Generic method to parse entities using a factory function.
     * The registry dynamically determines the appropriate parser based on the reflection object.
     *
     * @param array $entityNames List of entity names to parse
     * @param callable $reflectionFactory Factory function that creates reflection objects from names
     */
    private function parseEntities(array $entityNames, callable $reflectionFactory): void
    {
        foreach ($entityNames as $entityName) {
            try {
                $reflectionObject = $reflectionFactory($entityName);

                $parser = $this->parsersRegistry->findParserForObject($reflectionObject);
                if ($parser) {
                    $entity = $parser->parse($reflectionObject);
                    $this->storageManager->addEntity($entity);
                }
            } catch (\Exception $e) {
                // Skip entities that cannot be reflected
                continue;
            }
        }
    }

    /**
     * Parse all internal constants from runtime reflection.
     * Handles both PHP 8.1+ (ReflectionConstant) and older versions (array format).
     */
    private function parseConstants(): void
    {
        $reflectionConstants = $this->dataProvider->getReflectionConstants();

        // Check if ReflectionConstant class exists (PHP 8.1+)
        if (class_exists('\ReflectionConstant')) {
            // Filter to only defined constants and get their names
            $constantNames = array_filter(array_keys($reflectionConstants), function($name) {
                return defined($name);
            });

            $this->parseEntities(
                $constantNames,
                function($name) {
                    return new \ReflectionConstant($name);
                }
            );
        } else {
            // For PHP < 8.1, use array format
            // Convert associative array to list of [name => value] entries
            $constantEntries = array_map(
                function($name, $value) {
                    return [$name => $value];
                },
                array_keys($reflectionConstants),
                array_values($reflectionConstants)
            );

            $this->parseEntities(
                $constantEntries,
                function($entry) {
                    return $entry; // Already in array format expected by parser
                }
            );
        }
    }
}
