<?php

namespace StubTests\Sources\Parsers;

interface ParsedDataStorageProvider
{
    public function getEntities();

    public function addEntity($entity);
}