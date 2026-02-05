<?php

namespace StubTests\Sources\Parsers;

use StubTests\Sources\Model;

interface ParsedDataStorageProvider
{
    public function getEntities();

    public function addEntity($entity);

    /**
     * Load entities from persistent storage (if applicable)
     */
    public function load(): void;

    /**
     * Save entities to persistent storage (if applicable)
     */
    public function save(): void;
}