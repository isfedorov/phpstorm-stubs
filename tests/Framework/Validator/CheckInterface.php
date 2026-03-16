<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;

interface CheckInterface
{
    public function supports(string $phpVersion): bool;

    /**
     * @param ParsedDataStorageManager $stubs Parsed stubs data
     * @param string $entityId Entity identifier to validate
     * @param string $phpVersion PHP version string
     * @return CheckResultSet
     */
    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet;
}