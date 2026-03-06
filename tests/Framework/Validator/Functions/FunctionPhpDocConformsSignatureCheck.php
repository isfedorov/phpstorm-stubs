<?php

namespace StubTests\Sources\Validator\Functions;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractCallableCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\PhpDocConformanceTrait;
use StubTests\Sources\Validator\ReturnTypeHelperTrait;

/**
 * Validates that PhpDoc types in stubs are compatible with their signature types
 * for global functions.
 *
 * This is a stubs-only check (reflection data is never used). For each function
 * identified by $entityId the validator:
 * 1. Looks up the function in stubs using version-aware selection.
 * 2. If not found, silently succeeds — FunctionExistsCheck handles absence.
 * 3. For both return type and each parameter, compares the signature type with the
 *    PhpDoc type using the permissive compatibility algorithm in PhpDocConformanceTrait.
 * 4. Reports mismatches where sig and PhpDoc types share no common component.
 *
 * Intentional patterns (typed-array narrowing, phpstan generics, resource widening,
 * bool/false split) are accepted by the algorithm and will not be reported.
 *
 * Known problems are supported at function level:
 * - EntityType::FUNCTION + functionId + 'PhpDocConformsSignatureCheck'
 *   → skips the check for that specific function.
 */
class FunctionPhpDocConformsSignatureCheck extends AbstractCallableCheck
{
    use PhpDocConformanceTrait;
    use ReturnTypeHelperTrait;

    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, EntityType::FUNCTION->value, $entityId, 'PhpDocConformsSignatureCheck', $phpVersion)) {
            return $results;
        }

        $stubFunction = $this->findCallable($stubs, $entityId, $phpVersion);

        if ($stubFunction === null) {
            // Function absent from stubs — FunctionExistsCheck's responsibility
            $results->addSuccess($entityId);
            return $results;
        }

        // @template variable names declared on the function — compatible with any signature type
        $templateNames = $this->extractTemplateNames(
            method_exists($stubFunction, 'getPhpDoc') ? $stubFunction->getPhpDoc() : null
        );

        $mismatches = [];

        // Check return type
        $sigReturnType = $this->getReturnTypeString($stubFunction, $phpVersion);
        $docReturnType = method_exists($stubFunction, 'getReturnTypeFromPhpDoc')
            ? $stubFunction->getReturnTypeFromPhpDoc()
            : null;

        if ($sigReturnType !== null && $sigReturnType !== ''
            && $docReturnType !== null && $docReturnType !== ''
        ) {
            if (!$this->isPhpDocCompatibleWithSignature($sigReturnType, $docReturnType, $templateNames)) {
                $mismatches[] = "return type: sig '{$sigReturnType}', phpdoc '{$docReturnType}'";
            }
        }

        // Check each parameter
        $params = method_exists($stubFunction, 'getParameters') ? $stubFunction->getParameters() : [];
        foreach ($this->filterAndDeduplicateParamsPhpDoc($params, $phpVersion) as $param) {
            $sigType = $this->getParamSigTypeForPhpDoc($param, $phpVersion);
            $docType = $param->getTypeFromPhpDoc();

            if ($sigType === null || $sigType === '' || $docType === null || $docType === '') {
                continue;
            }

            if (!$this->isPhpDocCompatibleWithSignature($sigType, $docType, $templateNames)) {
                $mismatches[] = "\${$param->getName()}: sig '{$sigType}', phpdoc '{$docType}'";
            }
        }

        if (!empty($mismatches)) {
            $results->addFailure(
                $entityId,
                "Function {$entityId} PhpDoc/signature type mismatch in PHP {$phpVersion}: "
                . implode('; ', $mismatches)
            );
            return $results;
        }

        $results->addSuccess($entityId);
        return $results;
    }
}
