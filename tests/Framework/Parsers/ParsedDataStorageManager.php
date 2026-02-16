<?php

namespace StubTests\Sources\Parsers;

use StubTests\Framework\Parsers\Processors\EntityProcessingPipeline;

interface ParsedDataStorageManager
{
    public function getParsedDataStorageProvider(): ParsedDataStorageProvider;

    public function getAllEntities();

    // Write operations (processed immediately through pipeline)
    public function addClass($entity);
    public function addFunction($entity);
    public function addInterface($entity);
    public function addEnum($entity);
    public function addConstant($entity);

    // Generic add that auto-detects entity type
    public function addEntity($entity);

    // Write operations (deferred - raw, no processing)
    public function addEntityRaw($entity): void;

    // Processing
    public function process(): void;

    // Persistence
    public function save(): void;
    public function load(): void;

    // Query operations
    public function getClasses();
    public function hasClass(string $id): bool;
    public function getFunctions();
    public function getInterfaces();
    public function getEnums();
    public function getConstants();

    // Pipeline access
    public function getPipeline(): EntityProcessingPipeline;
}