<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\Entities\Model\PHPMethod;

/**
 * Validates that the number of parameters in stub methods matches reflection.
 *
 * For each class identified by $entityId the validator:
 * 1. Iterates all methods reported by reflection for the class.
 * 2. Looks up each method in the version-filtered stub hierarchy (parent classes
 *    and interfaces), stripping PS_UNRESERVE_PREFIX_ where needed.
 * 3. If the stub method is not found it is silently skipped — existence is
 *    ClassMethodsExistCheck's responsibility.
 * 4. When both sides are found, the stub parameter list is filtered by version
 *    (PhpStormStubsElementAvailable `from`/`to` → sinceVersion/removedVersion)
 *    and the resulting count is compared with the reflection count.
 *
 * Parameter version filtering uses inclusive boundaries for removedVersion (`<=`),
 * consistent with how PhpStormStubsElementAvailable `to` is interpreted elsewhere
 * (e.g. `to: '7.1'` means the parameter is still available in PHP 7.1).
 *
 * Known problems are supported at two granularities:
 * - class-level: EntityType::CLASS_TYPE + classId + 'ClassMethodsParametersCountCheck'
 *   → skips all parameter-count checks for the class.
 * - method-level: EntityType::METHOD + '\ClassName::methodName' + 'ClassMethodsParametersCountCheck'
 *   → skips only that specific mismatch.
 */
class ClassMethodsParametersCountCheck extends AbstractMethodFlagCheck
{
    protected function getCheckName(): string
    {
        return 'ClassMethodsParametersCountCheck';
    }

    protected function describeMismatch(
        string $methodEntityId,
        mixed $reflMethod,
        PHPMethod $stubMethod,
        string $phpVersion
    ): ?string {
        $reflCount = count($reflMethod->getParameters());

        $stubCount = 0;
        foreach ($stubMethod->getParameters() as $param) {
            $sinceVersion   = $param->getSinceVersion();
            $removedVersion = $param->getRemovedVersion();

            $available = ($sinceVersion === null || version_compare($phpVersion, $sinceVersion, '>='))
                && ($removedVersion === null || version_compare($phpVersion, $removedVersion, '<='));

            if ($available) {
                $stubCount++;
            }
        }

        if ($reflCount === $stubCount) {
            return null;
        }

        return "Method {$methodEntityId} has {$reflCount} parameter(s) in PHP {$phpVersion} but {$stubCount} in stubs";
    }
}
