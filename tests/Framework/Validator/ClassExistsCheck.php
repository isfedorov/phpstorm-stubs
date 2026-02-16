<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;

class ClassExistsCheck implements CheckInterface
{
    public function supports(string $phpVersion): bool
    {
        // Эта проверка поддерживается на всех версиях
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $classId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if (!$stubs->hasClass($classId)) {
            $results->addFailure($classId, "Class {$classId} exists in PHP {$phpVersion} but not in stubs");
        } else {
            $results->addSuccess($classId);
        }

        return $results;
    }
}