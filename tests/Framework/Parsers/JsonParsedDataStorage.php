<?php

namespace StubTests\Sources\Parsers;

use StubTests\Sources\Parsers\Serializers\EntitySerializerInterface;

/**
 * JSON storage for parsed entities.
 * Handles file I/O operations while delegating serialization to a pluggable serializer.
 * Optionally coordinates with PhpDocStorage for separated PhpDoc storage.
 */
class JsonParsedDataStorage implements ParsedDataPersistentStorageProvider
{
    private string $pathToJsonFile;
    private EntitySerializerInterface $serializer;
    private ?PhpDocStorage $phpDocStorage = null;
    private array $entities = [];
    private bool $loaded = false;

    /**
     * @param string $pathToJsonFile Path to JSON file
     * @param EntitySerializerInterface $serializer Serializer to use (StubsEntitySerializer or ReflectionEntitySerializer)
     * @param bool $loadExisting Whether to load existing file
     * @param PhpDocStorage|null $phpDocStorage Optional PhpDoc storage for separated PhpDoc
     */
    public function __construct(
        string $pathToJsonFile,
        EntitySerializerInterface $serializer,
        bool $loadExisting = true,
        ?PhpDocStorage $phpDocStorage = null
    ) {
        $this->pathToJsonFile = $pathToJsonFile;
        $this->serializer = $serializer;
        $this->phpDocStorage = $phpDocStorage;
        if ($loadExisting) {
            $this->load();
        } else {
            $this->loaded = true;
        }
    }

    public function getEntities()
    {
        return $this->entities;
    }

    public function addEntity($entity)
    {
        $this->entities[] = $entity;
    }

    public function clearEntities(): void
    {
        $this->entities = [];
    }

    public function save(): void
    {
        $serializedData = [];
        foreach ($this->entities as $entity) {
            try {
                $serializedData[] = $this->serializer->serialize($entity);
            } catch (\Exception $e) {
                // Skip entities that can't be serialized
                error_log("Warning: Could not serialize entity: " . $e->getMessage());
                continue;
            }
        }

        $dir = dirname($this->pathToJsonFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        // Use JSON_PARTIAL_OUTPUT_ON_ERROR to handle encoding errors gracefully
        $json = json_encode(
            $serializedData,
            JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES
        );

        if ($json === false || $json === 'null') {
            throw new \RuntimeException('JSON encoding failed: ' . json_last_error_msg());
        }

        $bytes = file_put_contents($this->pathToJsonFile, $json);

        if ($bytes === false) {
            throw new \RuntimeException('Failed to write to file: ' . $this->pathToJsonFile);
        }

        // Save PhpDocStorage if present
        if ($this->phpDocStorage !== null) {
            $this->phpDocStorage->save();
        }
    }

    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        if (!file_exists($this->pathToJsonFile)) {
            $this->entities = [];
            $this->loaded = true;
            return;
        }

        $jsonContent = file_get_contents($this->pathToJsonFile);
        if ($jsonContent === false || trim($jsonContent) === '') {
            $this->entities = [];
            $this->loaded = true;
            return;
        }

        $data = json_decode($jsonContent, true);
        if (!is_array($data)) {
            $this->entities = [];
            $this->loaded = true;
            return;
        }

        foreach ($data as $entityData) {
            if (isset($entityData['_type'])) {
                $this->entities[] = $this->serializer->deserialize($entityData);
            }
        }

        $this->loaded = true;
    }
}
