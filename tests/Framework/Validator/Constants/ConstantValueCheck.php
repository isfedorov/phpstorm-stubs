<?php

namespace StubTests\Sources\Validator\Constants;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\AbstractReflectionCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that the values of global constants in stubs match reflection.
 *
 * Value comparison is intentionally limited to the latest PHP version to avoid
 * false positives from historical value changes across PHP releases.
 *
 * For each constant identified by $entityId the validator:
 * 1. Skips comparison for non-LATEST PHP versions.
 * 2. Looks up the constant in reflection by ID.
 * 3. Looks up the constant in stubs by ID.
 * 4. If not found in either, silently succeeds — ConstantExistsCheck handles existence.
 * 5. Skips if either value is null (complex/dynamic expressions cannot be compared).
 * 6. Skips resource values stored as 'PHPSTORM_RESOURCE' by the reflection parser.
 * 7. Reports a failure if the string representations of the values differ.
 *
 * Known problems are supported via EntityType::GLOBAL_CONSTANT + constantId + 'ConstantValueCheck'.
 */
class ConstantValueCheck extends AbstractReflectionCheck
{
    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, EntityType::GLOBAL_CONSTANT->value, $entityId, 'ConstantValueCheck', $phpVersion)) {
            return $results;
        }

        // Value comparison is only meaningful at the latest PHP version
        if ($phpVersion !== PhpVersions::LATEST->value) {
            $results->addSuccess($entityId);
            return $results;
        }

        $reflection = $this->reflectionProvider->getReflection($phpVersion);

        // Find constant in reflection
        $reflConstant = null;
        foreach ($reflection->getConstants() as $constant) {
            if (method_exists($constant, 'getId') && $constant->getId() === $entityId) {
                $reflConstant = $constant;
                break;
            }
        }

        if ($reflConstant === null) {
            $results->addSuccess($entityId);
            return $results;
        }

        // Find constant in stubs
        $stubConstant = null;
        foreach ($stubs->getConstants() as $constant) {
            if (method_exists($constant, 'getId') && $constant->getId() === $entityId) {
                $stubConstant = $constant;
                break;
            }
        }

        if ($stubConstant === null) {
            // Not in stubs — ConstantExistsCheck's responsibility
            $results->addSuccess($entityId);
            return $results;
        }

        // Skip resource values — stored as 'PHPSTORM_RESOURCE' by the reflection parser
        if ($reflConstant->value === 'PHPSTORM_RESOURCE') {
            $results->addSuccess($entityId);
            return $results;
        }

        // Skip if either value is null (complex expressions cannot be compared)
        if ($reflConstant->value === null || $stubConstant->value === null) {
            $results->addSuccess($entityId);
            return $results;
        }

        if ((string) $reflConstant->value !== (string) $stubConstant->value) {
            $results->addFailure(
                $entityId,
                "Constant {$entityId} value mismatch: reflection='{$reflConstant->value}', stub='{$stubConstant->value}'"
            );
            return $results;
        }

        $results->addSuccess($entityId);
        return $results;
    }
}
