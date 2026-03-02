<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\CheckInterface;
use StubTests\Sources\Validator\CheckResultSet;

class InterfaceExistsCheck implements CheckInterface
{
    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if (!$stubs->hasInterface($entityId)) {
            $results->addFailure($entityId, "Interface {$entityId} exists in PHP {$phpVersion} but not in stubs");
        } else {
            $results->addSuccess($entityId);
        }

        return $results;
    }
}
