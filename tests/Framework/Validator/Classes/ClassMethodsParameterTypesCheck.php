<?php

namespace StubTests\Sources\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Validator\AbstractMethodFlagCheck;
use StubTests\Sources\Validator\TypeHelperTrait;

/**
 * Validates that parameter types in stub methods match those in reflection.
 *
 * For each class identified by $entityId the validator:
 * 1. Iterates all methods reported by reflection for the class.
 * 2. Looks up each method in the version-filtered stub hierarchy (parent classes
 *    and interfaces), stripping PS_UNRESERVE_PREFIX_ where needed.
 * 3. If the stub method is not found it is silently skipped — existence is
 *    ClassMethodsExistCheck's responsibility.
 * 4. When both sides are found, for each parameter present in both reflection
 *    and stubs (matched by name), types are resolved and compared.
 *
 * Type resolution priority (stub side):
 *   1. Signature type from getDeclaredType() — if non-empty (not NoType), used as-is.
 *   2. LanguageLevelTypeAware — highest version <= $phpVersion wins; default type fallback.
 *
 * Special cases:
 *   - Reflection has no type but stub documents one → skip (stubs are more informative).
 *   - Both sides have no type → treated as a match.
 *   - Reflection has a type but stub declares none → reported as a failure.
 *   - Parameter absent from stubs by name → silently skipped (ParametersCountCheck's
 *     responsibility).
 *
 * Known problems are supported at two granularities:
 * - class-level:  EntityType::CLASS_TYPE + classId + 'ParameterTypesCheck'
 *   → skips all parameter-type checks for the class.
 * - method-level: EntityType::METHOD + '\ClassName::methodName' + 'ParameterTypesCheck'
 *   → skips only that specific method.
 */
class ClassMethodsParameterTypesCheck extends AbstractMethodFlagCheck
{
    use TypeHelperTrait;

    public function supports(string $phpVersion): bool
    {
        // Scalar type hints were introduced in PHP 7.0
        return version_compare($phpVersion, '7.0', '>=');
    }

    protected function getCheckName(): string
    {
        return 'ParameterTypesCheck';
    }

    protected function describeMismatch(
        string $methodEntityId,
        mixed $reflMethod,
        PHPMethod $stubMethod,
        string $phpVersion
    ): ?string {
        // Build version-filtered stub param map by name, deduplicating variadic workarounds
        $stubParamsByName = [];
        foreach ($this->filterAndDeduplicateParams($stubMethod->getParameters(), $phpVersion) as $param) {
            $stubParamsByName[$param->getName()] = $param;
        }

        $mismatches = [];
        foreach ($reflMethod->getParameters() as $reflParam) {
            $name = $reflParam->getName();

            if (!isset($stubParamsByName[$name])) {
                // Parameter absent from stubs — ParametersCountCheck's responsibility
                continue;
            }

            $reflType = $this->getParamTypeString($reflParam, $phpVersion);

            // Reflection has no type — stubs may document one; skip this param
            if ($reflType === null) {
                continue;
            }

            $stubType = $this->getParamTypeString($stubParamsByName[$name], $phpVersion);

            $normalizedRefl = $this->normalizeType($reflType);
            $normalizedStub = $this->normalizeType($stubType);

            if ($normalizedRefl !== $normalizedStub) {
                $display = $stubType ?? 'none';
                $mismatches[] = "\${$name}: reflection '{$reflType}', stubs '{$display}'";
            }
        }

        if (empty($mismatches)) {
            return null;
        }

        return "Method {$methodEntityId}: parameter type mismatch(es) in PHP {$phpVersion}: "
            . implode('; ', $mismatches);
    }

    /**
     * Resolve the effective type string for a parameter at the given PHP version.
     *
     * Priority:
     * 1. Signature type from getDeclaredType() — if non-empty (not NoType), returned directly.
     * 2. LanguageLevelTypeAware — highest version entry <= $phpVersion, or defaultType.
     */
    private function getParamTypeString(PHPParameter $param, string $phpVersion): ?string
    {
        $declaredType = $param->getDeclaredType();

        $typeString = '';
        if (method_exists($declaredType, '__toString')) {
            $typeString = (string) $declaredType;
        } elseif (method_exists($declaredType, 'toString')) {
            $typeString = $declaredType->toString();
        }

        if ($typeString !== '') {
            return $typeString;
        }

        // No signature type → try LanguageLevelTypeAware
        $versionAwareType = $this->resolveVersionAwareType($param, $phpVersion);
        if ($versionAwareType !== null && $versionAwareType !== '') {
            return $versionAwareType;
        }

        return null;
    }

    /**
     * Filter parameters by version availability, then deduplicate consecutive same-named
     * parameters where the second is variadic (the stub workaround for non-optional variadics).
     *
     * @param  PHPParameter[] $params
     * @return PHPParameter[]
     */
    private function filterAndDeduplicateParams(array $params, string $phpVersion): array
    {
        $filtered = [];
        foreach ($params as $param) {
            $since   = $param->getSinceVersion();
            $removed = $param->getRemovedVersion();

            $available = ($since === null || version_compare($phpVersion, $since, '>='))
                && ($removed === null || version_compare($phpVersion, $removed, '<='));

            if ($available) {
                $filtered[] = $param;
            }
        }

        // Merge consecutive same-named params where the second is variadic
        $merged = [];
        $count  = count($filtered);
        for ($i = 0; $i < $count; $i++) {
            $current = $filtered[$i];
            $next    = $filtered[$i + 1] ?? null;

            if ($next !== null
                && $current->getName() === $next->getName()
                && $next->isVariadic()
            ) {
                $merged[] = $next;
                $i++;
            } else {
                $merged[] = $current;
            }
        }

        return $merged;
    }
}
