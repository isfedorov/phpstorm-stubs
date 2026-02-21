<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;

/**
 * Validates that functions from reflection exist in stubs.
 */
class FunctionExistsCheck implements CheckInterface
{
    public function supports(string $phpVersion): bool
    {
        // This check is supported on all PHP versions
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        // Check if function exists in stubs
        $stubFunctions = $stubs->getFunctions();
        $found = false;

        foreach ($stubFunctions as $stubFunction) {
            if (method_exists($stubFunction, 'getId') && $stubFunction->getId() === $entityId) {
                $found = true;
                break;
            } elseif (method_exists($stubFunction, 'getName') && $stubFunction->getName() === $entityId) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $results->addFailure(
				$entityId,
	            "Function {$entityId} exists in PHP {$phpVersion} but not in stubs"
            );
        } else {
            $results->addSuccess($entityId);
        }

        return $results;
    }
}
