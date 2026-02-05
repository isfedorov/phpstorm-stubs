<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Parsers\ParsedDataStorageProvider;

interface CheckInterface
{
    public function supports(string $phpVersion): bool;

    /**
     * @template T of ParsedDataStorageProvider
     * @param T $reflection - данные из PHP версии
     * @param T $stubs - распаршенные стабы
     * @param string $phpVersion - версия языка
     * @return CheckResultSet
     */
    public function run(ParsedDataStorageManager $reflection, ParsedDataStorageManager $stubs, string $phpVersion): CheckResultSet;
}