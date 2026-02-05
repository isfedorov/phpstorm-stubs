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

    public function run(ParsedDataStorageManager $reflection, ParsedDataStorageManager $stubs, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        foreach ($reflection->getClasses() as $class) {
            $name = $class->getName();
            if (!$stubs->hasClass($name)) {
                $results->addFailure($name, "Class {$name} exists in PHP {$phpVersion} but not in stubs");
            } else {
                $results->addSuccess($name);
            }
        }

        return $results;
    }
}