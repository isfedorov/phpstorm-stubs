<?php

namespace StubTests\Sources\Parsers;

use StubTests\Sources\Model;

class InMemoryParsedDataStorage implements ParsedDataStorageProvider
{
    private array $entities = [];
    private array $classes = [];
    private array $functions = [];
    private array $interfaces = [];
    private array $traits = [];
    private array $constants = [];
    private array $enums = [];

    public function __construct()
    {
    }


    public function getEntities()
    {
        return $this->entities;
    }

    public function addEntity($entity)
    {
        $this->entities[] = $entity;

        // Also categorize by type for faster lookups
        if ($entity instanceof Model\Entities\PHPClass) {
            $this->classes[] = $entity;
        } elseif ($entity instanceof Model\Entities\PHPFunction) {
            $this->functions[] = $entity;
        } elseif ($entity instanceof Model\Entities\PHPInterface) {
            $this->interfaces[] = $entity;
        } elseif ($entity instanceof Model\Entities\PHPEnum) {
            $this->enums[] = $entity;
        } elseif ($entity instanceof Model\Entities\PHPConstant) {
            $this->constants[] = $entity;
        }
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    public function getEnums(): array
    {
        return $this->enums;
    }

    public function getConstants(): array
    {
        return $this->constants;
    }

    /**
     * No-op for in-memory storage (nothing to load)
     */
    public function load(): void
    {
        // No persistent storage to load from
    }

    /**
     * No-op for in-memory storage (nothing to save)
     */
    public function save(): void
    {
        // No persistent storage to save to
    }

}