<?php

namespace StubTests\Sources\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Validator\AbstractMethodFlagCheck;

/**
 * Validates that parameter names in stub class methods match those in reflection.
 *
 * Named parameters were introduced in PHP 8.0, so this check only applies to PHP 8.0+.
 * Names are compared positionally after version-filtering and variadic deduplication.
 *
 * When a parameter name changed between PHP versions, stubs keep the LATEST name and
 * a known problem entry covers the older versions where reflection used the old name.
 *
 * Known problems are supported at two granularities:
 * - class-level:  EntityType::CLASS_TYPE + classId + 'ParameterNamesCheck'
 *   → skips all parameter-name checks for the class.
 * - method-level: EntityType::METHOD + '\ClassName::methodName' + 'ParameterNamesCheck'
 *   → skips only that specific method.
 */
class ClassMethodsParameterNamesCheck extends AbstractMethodFlagCheck
{
    public function supports(string $phpVersion): bool
    {
        // Named parameters were introduced in PHP 8.0
        return version_compare($phpVersion, '8.0', '>=');
    }

    protected function getCheckName(): string
    {
        return 'ParameterNamesCheck';
    }

    protected function describeMismatch(
        string $methodEntityId,
        mixed $reflMethod,
        PHPMethod $stubMethod,
        string $phpVersion
    ): ?string {
        $stubParams = $this->filterAndDeduplicateParams($stubMethod->getParameters(), $phpVersion);
        $reflParams = $reflMethod->getParameters();

        // Count mismatch: not our responsibility — ParametersCountCheck handles it
        if (count($reflParams) !== count($stubParams)) {
            return null;
        }

        $mismatches = [];
        foreach ($reflParams as $index => $reflParam) {
            $reflName = $reflParam->getName();
            $stubName = $stubParams[$index]->getName();

            if ($reflName !== $stubName) {
                $mismatches[] = "#{$index}: reflection '\${$reflName}', stubs '\${$stubName}'";
            }
        }

        if (empty($mismatches)) {
            return null;
        }

        return "Method {$methodEntityId}: parameter name mismatch(es) in PHP {$phpVersion}: "
            . implode('; ', $mismatches);
    }
}
