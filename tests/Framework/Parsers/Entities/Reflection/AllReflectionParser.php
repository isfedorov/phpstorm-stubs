<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use StubTests\Sources\DataProvider\CurrentRuntimeReflectionRawDataProvider;
use StubTests\Sources\Parsers\ParsedDataStorageManager;

/**
 * Parses all PHP reflection entities (classes, interfaces, functions, enums, constants)
 * from the current runtime and stores them in the storage manager.
 */
class AllReflectionParser
{
    private CurrentRuntimeReflectionRawDataProvider $dataProvider;
    private ParsedDataStorageManager $storageManager;
    private ReflectionClassParser $classParser;
    private ReflectionFunctionParser $functionParser;
    private ReflectionInterfaceParser $interfaceParser;
    private ReflectionEnumParser $enumParser;
    private ReflectionModernConstantParser $constantParser;

    public function __construct(
        CurrentRuntimeReflectionRawDataProvider $dataProvider,
        ParsedDataStorageManager                $storageManager
    ) {
        $this->dataProvider = $dataProvider;
        $this->storageManager = $storageManager;

        // Initialize parsers
        $this->classParser = new ReflectionClassParser();
        $this->functionParser = new ReflectionFunctionParser();
        $this->interfaceParser = new ReflectionInterfaceParser();
        $this->enumParser = new ReflectionEnumParser();
        $this->constantParser = new ReflectionModernConstantParser();
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

        foreach ($reflectionClasses as $className) {
            try {
                $reflectionClass = new \ReflectionClass($className);

                if ($this->classParser->canParseReflectionClass($reflectionClass)) {
                    $phpClass = $this->classParser->parse($reflectionClass);
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

        foreach ($reflectionFunctions as $functionName) {
            try {
                $reflectionFunction = new \ReflectionFunction($functionName);
                $phpFunction = $this->functionParser->parse($reflectionFunction);
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

        foreach ($reflectionInterfaces as $interfaceName) {
            try {
                $reflectionClass = new \ReflectionClass($interfaceName);

                if ($this->interfaceParser->canParseReflectionClass($reflectionClass)) {
                    $phpInterface = $this->interfaceParser->parse($reflectionClass);
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

        foreach ($reflectionEnums as $enumName) {
            try {
                $reflectionClass = new \ReflectionClass($enumName);

                if ($this->enumParser->canParseReflectionClass($reflectionClass)) {
                    $phpEnum = $this->enumParser->parse($reflectionClass);
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

        // Check if ReflectionConstant class exists (PHP 8.1+)
        if (class_exists('\ReflectionConstant')) {
            foreach ($reflectionConstants as $constantName => $constantValue) {
                try {
                    // Try to create ReflectionConstant for namespaced constants
                    if (defined($constantName)) {
                        $reflectionConstant = new \ReflectionConstant($constantName);
                        $phpConstant = $this->constantParser->parse($reflectionConstant);
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
