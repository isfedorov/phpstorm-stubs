<?php

namespace StubTests\Sources\Validator\Functions;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\AbstractCallableCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\ParameterDefaultValueTrait;

/**
 * Validates that default parameter values in stub functions match those in reflection.
 *
 * Only runs against the latest PHP version since stubs do not support version-aware
 * default values (no LanguageLevelTypeAware equivalent for defaults).
 *
 * For each function identified by $entityId the validator:
 * 1. Looks up the function in reflection and stubs.
 * 2. If the stub function is not found it is silently skipped — existence is
 *    FunctionExistsCheck's responsibility.
 * 3. For each reflection parameter with an accessible default, checks whether
 *    the matching stub parameter (by name) declares the same evaluated default.
 * 4. Comparison is skipped when either side's value is null (see class-level check
 *    docs for the rationale).
 *
 * Known problems are supported at function level:
 * - EntityType::FUNCTION + functionId + 'ParameterDefaultValueCheck'
 */
class FunctionParameterDefaultValueCheck extends AbstractCallableCheck
{
    use ParameterDefaultValueTrait;

    public function supports(string $phpVersion): bool
    {
        return $phpVersion === PhpVersions::LATEST->value;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, EntityType::FUNCTION->value, $entityId, 'ParameterDefaultValueCheck', $phpVersion)) {
            return $results;
        }

        $reflection   = $this->reflectionProvider->getReflection($phpVersion);
        $reflFunction = $this->findCallable($reflection, $entityId, $phpVersion);

        if ($reflFunction === null) {
            $results->addFailure($entityId, "Function {$entityId} not found in reflection data");
            return $results;
        }

        $stubFunction = $this->findCallable($stubs, $entityId, $phpVersion);

        if ($stubFunction === null) {
            // Function absent from stubs — FunctionExistsCheck's responsibility
            $results->addSuccess($entityId);
            return $results;
        }

        // Build version-filtered stub param map by name
        // Last definition wins — handles placeholder + variadic same-name pairs
        $stubParamsByName = [];
        foreach ($stubFunction->getParameters() as $param) {
            $since   = $param->getSinceVersion();
            $removed = $param->getRemovedVersion();

            $available = ($since === null || version_compare($phpVersion, $since, '>='))
                && ($removed === null || version_compare($phpVersion, $removed, '<'));

            if ($available) {
                $stubParamsByName[$param->getName()] = $param;
            }
        }

        $mismatches = $this->buildParamMismatches($reflFunction->getParameters(), $stubParamsByName);

        if (!empty($mismatches)) {
            $results->addFailure(
                $entityId,
                "Function {$entityId}: parameter default value mismatch(es): " . implode('; ', $mismatches)
            );
            return $results;
        }

        $results->addSuccess($entityId);
        return $results;
    }
}
