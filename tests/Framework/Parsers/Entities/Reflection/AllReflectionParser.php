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
        $this->parseClasses();
        $this->parseFunctions();
        $this->parseInterfaces();
        $this->parseEnums();
        $this->parseConstants();
    }

    /**
     * Parse all internal classes from runtime reflection.
     */
    private function parseClasses(): void
    {
        $reflectionClasses = $this->dataProvider->getReflectionClasses();
        $classParser = $this->parsersRegistry->findParser(EntityType::A_CLASS);

        foreach ($reflectionClasses as $className) {
            try {
                $nativeReflection = new \ReflectionClass($className);
                $adaptedReflection = new AdaptedReflectionClass($nativeReflection);

                if ($classParser->canParseReflectionClass($adaptedReflection)) {
                    $phpClass = $classParser->parse($adaptedReflection);
                    $this->storageManager->addEntity($phpClass);
                }
            } catch (\Exception $e) {
                // Skip classes that cannot be reflected
                continue;
            }
        }
    }

    /**
     * Parse all internal functions from runtime reflection.
     */
    private function parseFunctions(): void
    {
        $reflectionFunctions = $this->dataProvider->getReflectionFunctions();
        $functionParser = $this->parsersRegistry->findParser(EntityType::FUNCTION);

        foreach ($reflectionFunctions as $functionName) {
            try {
                $nativeReflection = new \ReflectionFunction($functionName);
                $adaptedReflection = new AdaptedReflectionFunction($nativeReflection);

                $phpFunction = $functionParser->parse($adaptedReflection);
                $this->storageManager->addEntity($phpFunction);
            } catch (\Exception $e) {
                // Skip functions that cannot be reflected
                continue;
            }
        }
    }

    /**
     * Parse all internal interfaces from runtime reflection.
     */
    private function parseInterfaces(): void
    {
        $reflectionInterfaces = $this->dataProvider->getReflectionInterfaces();
        $interfaceParser = $this->parsersRegistry->findParser(EntityType::INTERFACE);

        foreach ($reflectionInterfaces as $interfaceName) {
            try {
                $nativeReflection = new \ReflectionClass($interfaceName);
                $adaptedReflection = new AdaptedReflectionClass($nativeReflection);

                if ($interfaceParser->canParseReflectionClass($adaptedReflection)) {
                    $phpInterface = $interfaceParser->parse($adaptedReflection);
                    $this->storageManager->addEntity($phpInterface);
                }
            } catch (\Exception $e) {
                // Skip interfaces that cannot be reflected
                continue;
            }
        }
    }

    /**
     * Parse all internal enums from runtime reflection.
     */
    private function parseEnums(): void
    {
        $reflectionEnums = $this->dataProvider->getReflectionEnums();
        $enumParser = $this->parsersRegistry->findParser(EntityType::ENUM);

        foreach ($reflectionEnums as $enumName) {
            try {
                $nativeReflection = new \ReflectionClass($enumName);
                $adaptedReflection = new AdaptedReflectionClass($nativeReflection);

                if ($enumParser->canParseReflectionClass($adaptedReflection)) {
                    $phpEnum = $enumParser->parse($adaptedReflection);
                    $this->storageManager->addEntity($phpEnum);
                }
            } catch (\Exception $e) {
                // Skip enums that cannot be reflected
                continue;
            }
        }
    }

    /**
     * Parse all internal constants from runtime reflection.
     */
    private function parseConstants(): void
    {
        $reflectionConstants = $this->dataProvider->getReflectionConstants();
        $constantParser = $this->parsersRegistry->findParser(EntityType::CONSTANT);

        // Check if ReflectionConstant class exists (PHP 8.1+)
        if (class_exists('\ReflectionConstant')) {
            foreach ($reflectionConstants as $constantName => $constantValue) {
                try {
                    // Try to create ReflectionConstant for namespaced constants
                    if (defined($constantName)) {
                        $reflectionConstant = new \ReflectionConstant($constantName);
                        $phpConstant = $constantParser->parse($reflectionConstant);
                        $this->storageManager->addEntity($phpConstant);
                    }
                } catch (\Exception $e) {
                    // If ReflectionConstant fails, skip this constant
                    continue;
                }
            }
        } else {
            // For PHP < 8.1, use define constant parser
            $defineParser = new ReflectionDefineConstantParser();
            foreach ($reflectionConstants as $constantName => $constantValue) {
                try {
                    $phpConstant = $defineParser->parse([$constantName => $constantValue]);
                    $this->storageManager->addEntity($phpConstant);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }
}
