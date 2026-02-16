<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\DataProvider\StubsDataProvider;
use StubTests\Sources\Parsers\ParsedDataStorageManager;

class AllStubsParser
{
    public function __construct(
        private StubsDataProvider $dataProvider,
        private ParsedDataStorageManager $storageManager,
        private array $parsers // [StubClassParser, StubFunctionParser, ...]
    ) {}

    public function parseAll(): void
    {
        $files = $this->dataProvider->getAllStubFiles();

        // PHASE 1: Collect all entities (deferred processing)
        foreach ($files as $filePath) {
            $content = $this->dataProvider->getStubFileContent($filePath);

            // Parse with appropriate parsers
            foreach ($this->parsers as $parser) {
                try {
                    // Handle StubClassParser specially to support multiple classes per file
                    if ($parser instanceof StubClassParser) {
                        $classNodes = $parser->nodeExtractor->extractAllClasses($content);
                        foreach ($classNodes as $classNode) {
                            $entity = $parser->parseNode($classNode);
                            $entity->setSourcePath($filePath);
                            $this->storageManager->addEntityRaw($entity);
                        }
                    } elseif ($parser instanceof StubFunctionParser) {
                        // Handle StubFunctionParser specially to support multiple functions per file
                        $functionNodes = $parser->nodeExtractor->extractAllFunctions($content);
                        foreach ($functionNodes as $functionNode) {
                            $entity = $parser->parseNode($functionNode);
                            $entity->setSourcePath($filePath);
                            $this->storageManager->addEntityRaw($entity);
                        }
                    } elseif ($parser instanceof StubInterfaceParser) {
                        // Handle StubInterfaceParser specially to support multiple interfaces per file
                        $interfaceNodes = $parser->nodeExtractor->extractAllInterfaces($content);
                        foreach ($interfaceNodes as $interfaceNode) {
                            $entity = $parser->parseNode($interfaceNode);
                            $entity->setSourcePath($filePath);
                            $this->storageManager->addEntityRaw($entity);
                        }
                    } elseif ($parser instanceof StubEnumParser) {
                        // Handle StubEnumParser specially to support multiple enums per file
                        $enumNodes = $parser->nodeExtractor->extractAllEnums($content);
                        foreach ($enumNodes as $enumNode) {
                            $entity = $parser->parseNode($enumNode);
                            $entity->setSourcePath($filePath);
                            $this->storageManager->addEntityRaw($entity);
                        }
                    } elseif ($parser instanceof StubDefineConstantParser) {
                        // Handle StubDefineConstantParser specially to support multiple constants per file
                        $constantNodes = $parser->nodeExtractor->extractAllDefineConstants($content);
                        foreach ($constantNodes as $constantNode) {
                            $entity = $parser->parseNode($constantNode);
                            $entity->setSourcePath($filePath);
                            $this->storageManager->addEntityRaw($entity);
                        }
                    } elseif ($parser instanceof StubModernConstantParser) {
                        // Handle StubModernConstantParser specially to support multiple const declarations per file
                        $constantNodes = $parser->nodeExtractor->extractAllModernConstants($content);
                        foreach ($constantNodes as $constantNode) {
                            $entity = $parser->parseNode($constantNode);
                            $entity->setSourcePath($filePath);
                            $this->storageManager->addEntityRaw($entity);
                        }
                    } else {
                        // For other parsers, use the standard parse method
                        $result = $parser->parse($content);

                        if ($result === null) {
                            continue;
                        }

                        // Handle both single entities and arrays
                        if (is_array($result)) {
                            foreach ($result as $entity) {
                                if ($entity !== null) {
                                    $entity->setSourcePath($filePath);
                                    $this->storageManager->addEntityRaw($entity);
                                }
                            }
                        } else {
                            $result->setSourcePath($filePath);
                            $this->storageManager->addEntityRaw($result);
                        }
                    }
                } catch (\Exception $e) {
                    // Skip files that don't contain this entity type
                    continue;
                }
            }
        }

        // PHASE 2: Process all collected entities through pipeline (includes deduplication if configured)
        $this->storageManager->process();
    }
}