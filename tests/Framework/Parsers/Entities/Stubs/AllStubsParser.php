<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\DataProvider\StubsDataProvider;
use StubTests\Sources\Parsers\ParsedDataStorageManager;

class AllStubsParser
{
    /**
     * @param StubsDataProvider $dataProvider
     * @param ParsedDataStorageManager $storageManager
     * @param MultiEntityStubParserInterface[] $parsers Array of parsers implementing MultiEntityStubParserInterface
     */
    public function __construct(
        private StubsDataProvider $dataProvider,
        private ParsedDataStorageManager $storageManager,
        private array $parsers
    ) {}

    public function parseAll(): void
    {
        $files = $this->dataProvider->getAllStubFiles();

        // PHASE 1: Collect all entities (deferred processing)
        foreach ($files as $filePath) {
            $content = $this->dataProvider->getStubFileContent($filePath);

            // Parse with all parsers using polymorphic interface
            foreach ($this->parsers as $parser) {
                try {
                    $entities = $parser->extractAndParseAll($content);

                    foreach ($entities as $entity) {
                        $entity->setSourcePath($filePath);
                        $this->storageManager->addEntityRaw($entity);
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