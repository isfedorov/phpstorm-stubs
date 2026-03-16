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

    private ?array $cachedClasses = null;
    private ?array $cachedFunctions = null;
    private ?array $cachedInterfaces = null;
    private ?array $cachedEnums = null;
    private ?array $cachedConstants = null;

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

    private function invalidateCache(): void
    {
        $this->cachedClasses = null;
        $this->cachedFunctions = null;
        $this->cachedInterfaces = null;
        $this->cachedEnums = null;
        $this->cachedConstants = null;
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
            $this->invalidateCache();
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
            $this->invalidateCache();
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
            $this->invalidateCache();
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
            $this->invalidateCache();
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
            $this->invalidateCache();
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
                $this->invalidateCache();
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
        $this->invalidateCache();
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
            $this->invalidateCache();
        }
    }

    public function getClasses()
    {
        if ($this->cachedClasses === null) {
            $allEntities = $this->parsedDataStorageProvider->getEntities();
            $this->cachedClasses = is_array($allEntities)
                ? array_filter($allEntities, fn($e) => $e instanceof PHPClass)
                : [];
        }
        return $this->cachedClasses;
    }

    public function hasClass(string $id): bool
    {
        foreach ($this->getClasses() as $class) {
            if ($class->getId() === $id) {
                return true;
            }
        }
        return false;
    }

    public function getFunctions()
    {
        if ($this->cachedFunctions === null) {
            $allEntities = $this->parsedDataStorageProvider->getEntities();
            $this->cachedFunctions = is_array($allEntities)
                ? array_filter($allEntities, fn($e) => $e instanceof PHPFunction)
                : [];
        }
        return $this->cachedFunctions;
    }

    public function getInterfaces()
    {
        if ($this->cachedInterfaces === null) {
            $allEntities = $this->parsedDataStorageProvider->getEntities();
            $this->cachedInterfaces = is_array($allEntities)
                ? array_filter($allEntities, fn($e) => $e instanceof PHPInterface)
                : [];
        }
        return $this->cachedInterfaces;
    }

    public function hasInterface(string $id): bool
    {
        foreach ($this->getInterfaces() as $interface) {
            if ($interface->getId() === $id) {
                return true;
            }
        }
        return false;
    }

    public function getEnums()
    {
        if ($this->cachedEnums === null) {
            $allEntities = $this->parsedDataStorageProvider->getEntities();
            $this->cachedEnums = is_array($allEntities)
                ? array_filter($allEntities, fn($e) => $e instanceof PHPEnum)
                : [];
        }
        return $this->cachedEnums;
    }

    public function hasEnum(string $id): bool
    {
        foreach ($this->getEnums() as $enum) {
            if ($enum->getId() === $id) {
                return true;
            }
        }
        return false;
    }

    public function getConstants()
    {
        if ($this->cachedConstants === null) {
            $allEntities = $this->parsedDataStorageProvider->getEntities();
            $this->cachedConstants = is_array($allEntities)
                ? array_filter($allEntities, fn($e) => $e instanceof PHPConstant)
                : [];
        }
        return $this->cachedConstants;
    }
}

