<?php

namespace StubTests\Sources\Parsers;

use StubTests\Framework\Parsers\Processors\EntityProcessingPipeline;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;

class DefaultParsedDataStorageManager implements ParsedDataStorageManager
{
    private ParsedDataStorageProvider $parsedDataStorageProvider;
    private EntityProcessingPipeline $pipeline;
    private array $rawEntities = [];

    public function __construct(
        ParsedDataStorageProvider $parsedDataStorageProvider,
        ?EntityProcessingPipeline $pipeline = null
    ) {
        $this->parsedDataStorageProvider = $parsedDataStorageProvider;
        $this->pipeline = $pipeline ?? new EntityProcessingPipeline();
    }

    public function getParsedDataStorageProvider(): ParsedDataStorageProvider
    {
        return $this->parsedDataStorageProvider;
    }

    public function getPipeline(): EntityProcessingPipeline
    {
        return $this->pipeline;
    }

    public function getAllEntities()
    {
        return $this->parsedDataStorageProvider->getEntities();
    }

    /**
     * Add entity with immediate processing through pipeline
     */
    public function addClass($entity)
    {
        $processed = $this->pipeline->processSingle(
            $entity,
            ['existingEntities' => $this->parsedDataStorageProvider->getEntities()]
        );

        if ($processed !== null) {
            $this->parsedDataStorageProvider->addEntity($processed);
        }
    }

    public function addFunction($entity)
    {
        $processed = $this->pipeline->processSingle(
            $entity,
            ['existingEntities' => $this->parsedDataStorageProvider->getEntities()]
        );

        if ($processed !== null) {
            $this->parsedDataStorageProvider->addEntity($processed);
        }
    }

    public function addInterface($entity)
    {
        $processed = $this->pipeline->processSingle(
            $entity,
            ['existingEntities' => $this->parsedDataStorageProvider->getEntities()]
        );

        if ($processed !== null) {
            $this->parsedDataStorageProvider->addEntity($processed);
        }
    }

    public function addEnum($entity)
    {
        $processed = $this->pipeline->processSingle(
            $entity,
            ['existingEntities' => $this->parsedDataStorageProvider->getEntities()]
        );

        if ($processed !== null) {
            $this->parsedDataStorageProvider->addEntity($processed);
        }
    }

    public function addConstant($entity)
    {
        $processed = $this->pipeline->processSingle(
            $entity,
            ['existingEntities' => $this->parsedDataStorageProvider->getEntities()]
        );

        if ($processed !== null) {
            $this->parsedDataStorageProvider->addEntity($processed);
        }
    }

    /**
     * Generic add method that detects entity type and routes to appropriate method
     */
    public function addEntity($entity)
    {
        if ($entity instanceof PHPClass) {
            $this->addClass($entity);
        } elseif ($entity instanceof PHPFunction) {
            $this->addFunction($entity);
        } elseif ($entity instanceof PHPInterface) {
            $this->addInterface($entity);
        } elseif ($entity instanceof PHPEnum) {
            $this->addEnum($entity);
        } elseif ($entity instanceof PHPConstant) {
            $this->addConstant($entity);
        } else {
            // For unknown types, process directly
            $processed = $this->pipeline->processSingle(
                $entity,
                ['existingEntities' => $this->parsedDataStorageProvider->getEntities()]
            );

            if ($processed !== null) {
                $this->parsedDataStorageProvider->addEntity($processed);
            }
        }
    }

    /**
     * Add entity without processing (deferred)
     */
    public function addEntityRaw($entity): void
    {
        $this->rawEntities[] = $entity;
    }

    /**
     * Process all raw entities through pipeline
     */
    public function process(): void
    {
        if (empty($this->rawEntities)) {
            return;
        }

        $processed = $this->pipeline->processBatch($this->rawEntities);

        foreach ($processed as $entity) {
            $this->parsedDataStorageProvider->addEntity($entity);
        }

        $this->rawEntities = [];
    }

    /**
     * Save entities to persistent storage
     */
    public function save(): void
    {
        // Process any remaining raw entities before saving
        $this->process();

        // Delegate to storage provider if it supports persistence
        if ($this->parsedDataStorageProvider instanceof ParsedDataPersistentStorageProvider) {
            $this->parsedDataStorageProvider->save();
        }
    }

    /**
     * Load entities from persistent storage
     */
    public function load(): void
    {
        // Delegate to storage provider if it supports persistence
        if ($this->parsedDataStorageProvider instanceof ParsedDataPersistentStorageProvider) {
            $this->parsedDataStorageProvider->load();
        }
    }

    public function getClasses()
    {
        $allEntities = $this->parsedDataStorageProvider->getEntities();
        if (!is_array($allEntities)) {
            return [];
        }

        return array_filter($allEntities, function ($entity) {
            return $entity instanceof PHPClass;
        });
    }

    public function hasClass(string $id): bool
    {
        $classes = $this->getClasses();
        foreach ($classes as $class) {
            if ($class->getId() === $id) {
                return true;
            }
        }
        return false;
    }

    public function getFunctions()
    {
        $allEntities = $this->parsedDataStorageProvider->getEntities();
        if (!is_array($allEntities)) {
            return [];
        }

        return array_filter($allEntities, function ($entity) {
            return $entity instanceof PHPFunction;
        });
    }

    public function getInterfaces()
    {
        $allEntities = $this->parsedDataStorageProvider->getEntities();
        if (!is_array($allEntities)) {
            return [];
        }

        return array_filter($allEntities, function ($entity) {
            return $entity instanceof PHPInterface;
        });
    }

    public function getEnums()
    {
        $allEntities = $this->parsedDataStorageProvider->getEntities();
        if (!is_array($allEntities)) {
            return [];
        }

        return array_filter($allEntities, function ($entity) {
            return $entity instanceof PHPEnum;
        });
    }

    public function getConstants()
    {
        $allEntities = $this->parsedDataStorageProvider->getEntities();
        if (!is_array($allEntities)) {
            return [];
        }

        return array_filter($allEntities, function ($entity) {
            return $entity instanceof PHPConstant;
        });
    }
}

