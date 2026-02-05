<?php

namespace StubTests\Sources\Parsers;

use StubTests\Sources\Model;

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
        if ($entity instanceof Model\Entities\PHPClass) {
            $this->addClass($entity);
        } elseif ($entity instanceof Model\Entities\PHPFunction) {
            $this->addFunction($entity);
        } elseif ($entity instanceof Model\Entities\PHPInterface) {
            $this->addInterface($entity);
        } elseif ($entity instanceof Model\Entities\PHPEnum) {
            $this->addEnum($entity);
        } elseif ($entity instanceof Model\Entities\PHPConstant) {
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

        // Delegate to storage provider
        $this->parsedDataStorageProvider->save();
    }

    /**
     * Load entities from persistent storage
     */
    public function load(): void
    {
        $this->parsedDataStorageProvider->load();
    }

    public function getClasses()
    {
        $allEntities = $this->parsedDataStorageProvider->getEntities();
        if (!is_array($allEntities)) {
            return [];
        }

        return array_filter($allEntities, function ($entity) {
            return $entity instanceof Model\Entities\PHPClass;
        });
    }

    public function hasClass(string $name): bool
    {
        $classes = $this->getClasses();
        foreach ($classes as $class) {
            if ($class->getName() === $name) {
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
            return $entity instanceof Model\Entities\PHPFunction;
        });
    }

    public function getInterfaces()
    {
        $allEntities = $this->parsedDataStorageProvider->getEntities();
        if (!is_array($allEntities)) {
            return [];
        }

        return array_filter($allEntities, function ($entity) {
            return $entity instanceof Model\Entities\PHPInterface;
        });
    }

    public function getEnums()
    {
        $allEntities = $this->parsedDataStorageProvider->getEntities();
        if (!is_array($allEntities)) {
            return [];
        }

        return array_filter($allEntities, function ($entity) {
            return $entity instanceof Model\Entities\PHPEnum;
        });
    }

    public function getConstants()
    {
        $allEntities = $this->parsedDataStorageProvider->getEntities();
        if (!is_array($allEntities)) {
            return [];
        }

        return array_filter($allEntities, function ($entity) {
            return $entity instanceof Model\Entities\PHPConstant;
        });
    }
}

