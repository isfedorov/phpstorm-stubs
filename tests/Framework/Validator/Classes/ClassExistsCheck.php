<?php

namespace StubTests\Sources\Validator\Classes;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\CheckInterface;
use StubTests\Sources\Validator\CheckResultSet;

class ClassExistsCheck implements CheckInterface
{
    public function supports(string $phpVersion): bool
    {
        // Эта проверка поддерживается на всех версиях
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if (!$stubs->hasClass($entityId)) {
            $results->addFailure($entityId, "Class {$entityId} exists in PHP {$phpVersion} but not in stubs");
        } else {
            $results->addSuccess($entityId);
        }

        return $results;
    }
}