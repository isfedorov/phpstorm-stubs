<?php

namespace StubTests\Sources\Validator\Constants;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\CheckInterface;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblemsRegistry;

/**
 * Validates that global constants from reflection exist in stubs.
 */
class ConstantExistsCheck implements CheckInterface
{
    public function __construct(
        private readonly ?KnownProblemsRegistry $knownProblemsRegistry = null
    ) {}

    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();
        $registry = $this->knownProblemsRegistry ?? KnownProblemsRegistry::getInstance();

        if ($registry->shouldSkipValidation(
            EntityType::GLOBAL_CONSTANT->value,
            $entityId,
            'ConstantExistsCheck',
            $phpVersion
        )) {
            $reason = $registry->getSkipReason(
                EntityType::GLOBAL_CONSTANT->value,
                $entityId,
                'ConstantExistsCheck',
                $phpVersion
            );
            $results->addSuccess($entityId . ' (skipped: ' . $reason . ')');
            return $results;
        }

        foreach ($stubs->getConstants() as $constant) {
            if (method_exists($constant, 'getId') && $constant->getId() === $entityId) {
                $results->addSuccess($entityId);
                return $results;
            }
        }

        $results->addFailure($entityId, "Constant {$entityId} exists in PHP {$phpVersion} but not in stubs");
        return $results;
    }
}
