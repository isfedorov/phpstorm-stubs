<?php

namespace StubTests\Sources\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractClassCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\PhpDocConformanceTrait;
use StubTests\Sources\Validator\ReturnTypeHelperTrait;

/**
 * Validates that PhpDoc types in class stubs are compatible with their signature types.
 *
 * This is a stubs-only check (reflection data is never used). For each class
 * identified by $entityId the validator:
 * 1. Looks up the class in stubs. If not found, silently succeeds.
 * 2. Iterates all version-available stub methods: checks return type and each
 *    parameter type for PhpDoc/signature compatibility.
 * 3. Iterates all version-available stub properties: checks declared type vs PhpDoc type.
 * 4. Reports mismatches where sig and PhpDoc types share no common component.
 *
 * Intentional patterns (typed-array narrowing, phpstan generics, resource widening,
 * bool/false split) are accepted by the algorithm and will not be reported.
 *
 * Known problems are supported at three granularities:
 * - entity-level: entityType + entityId + 'PhpDocConformsSignatureCheck'
 *   → skips all method/property checks for the entity.
 * - method-level: EntityType::METHOD + 'ClassName::methodName' + 'PhpDocConformsSignatureCheck'
 *   → skips only that specific method.
 * - property-level: EntityType::PROPERTY + 'ClassName::$propName' + 'PhpDocConformsSignatureCheck'
 *   → skips only that specific property.
 */
class ClassMethodsPhpDocConformsSignatureCheck extends AbstractClassCheck
{
    use PhpDocConformanceTrait;
    use ReturnTypeHelperTrait;

    public function supports(string $phpVersion): bool
    {
        return true;
    }

    // ── Template methods (overridden by Enum and Interface subclasses) ─────────

    /**
     * Find the stub entity by entity ID.
     */
    protected function findStubEntity(ParsedDataStorageManager $stubs, string $entityId): mixed
    {
        return $this->findClassById($stubs, $entityId);
    }

    /**
     * Collect version-filtered methods for the entity.
     *
     * @return array<string, PHPMethod>
     */
    protected function getVersionedEntityMethods(mixed $entity, string $phpVersion): array
    {
        return $this->collectVersionedStubMethods($entity, $phpVersion);
    }

    /**
     * Collect version-filtered properties for the entity.
     * Returns empty array for entity types that have no properties (enums, interfaces).
     *
     * @return array<string, PHPProperty>
     */
    protected function getVersionedEntityProperties(mixed $entity, string $phpVersion): array
    {
        return $this->collectVersionedStubPropertiesMap($entity, $phpVersion);
    }

    /**
     * Label used in failure messages (e.g. "Class").
     */
    protected function getEntityLabel(): string
    {
        return 'Class';
    }

    /**
     * Entity type used for known-problem lookups.
     */
    protected function getEntityType(): string
    {
        return EntityType::CLASS_TYPE->value;
    }

    // ── Main run loop ──────────────────────────────────────────────────────────

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, $this->getEntityType(), $entityId, 'PhpDocConformsSignatureCheck', $phpVersion)) {
            return $results;
        }

        $stubEntity = $this->findStubEntity($stubs, $entityId);
        if ($stubEntity === null) {
            // Entity absent from stubs — ExistsCheck's responsibility
            $results->addSuccess($entityId);
            return $results;
        }

        $hasMismatch = false;

        // @template variable names declared on the entity (class/enum/interface).
        // These names are used to detect when a PhpDoc type is a type parameter rather than
        // a real class, so we can accept it as compatible with any signature type.
        $templateNames = $this->extractTemplateNames($stubEntity->getPhpDoc());

        // Check methods
        foreach ($this->getVersionedEntityMethods($stubEntity, $phpVersion) as $methodName => $method) {
            $mismatches = $this->collectMethodMismatches($method, $phpVersion, $templateNames);

            if (empty($mismatches)) {
                continue;
            }

            $methodEntityId = $entityId . '::' . $methodName;
            $hasMismatch = true;

            if (!$this->skipWithKnownProblem($results, EntityType::METHOD->value, $methodEntityId, 'PhpDocConformsSignatureCheck', $phpVersion)) {
                $results->addFailure(
                    $methodEntityId,
                    "{$this->getEntityLabel()} {$methodEntityId} PhpDoc/signature type mismatch in PHP {$phpVersion}: "
                    . implode('; ', $mismatches)
                );
            }
        }

        // Check properties
        foreach ($this->getVersionedEntityProperties($stubEntity, $phpVersion) as $propName => $property) {
            $sigType = $this->getPropertySigTypeForPhpDoc($property, $phpVersion);
            $docType = $property->getTypeFromPhpDoc();

            if ($sigType === null || $sigType === '' || $docType === null || $docType === '') {
                continue;
            }

            if (!$this->isPhpDocCompatibleWithSignature($sigType, $docType, $templateNames)) {
                $propEntityId = $entityId . '::$' . $propName;
                $hasMismatch = true;

                if (!$this->skipWithKnownProblem($results, EntityType::PROPERTY->value, $propEntityId, 'PhpDocConformsSignatureCheck', $phpVersion)) {
                    $results->addFailure(
                        $propEntityId,
                        "{$this->getEntityLabel()} {$propEntityId} PhpDoc/signature type mismatch in PHP {$phpVersion}: sig '{$sigType}', phpdoc '{$docType}'"
                    );
                }
            }
        }

        if (!$hasMismatch) {
            $results->addSuccess($entityId);
        }

        return $results;
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Collect PhpDoc/signature mismatches for a single method.
     * Returns an empty array when there are no mismatches.
     *
     * @param string[] $templateNames @template variable names declared on the enclosing entity
     * @return string[]
     */
    private function collectMethodMismatches(PHPMethod $method, string $phpVersion, array $templateNames = []): array
    {
        // Merge entity-level templates with any method-level @template declarations
        $allTemplateNames = array_merge($templateNames, $this->extractTemplateNames($method->getPhpDoc()));

        $mismatches = [];

        // Return type
        $sigReturnType = $this->getReturnTypeString($method, $phpVersion);
        $docReturnType = $method->getReturnTypeFromPhpDoc();

        if ($sigReturnType !== null && $sigReturnType !== ''
            && $docReturnType !== null && $docReturnType !== ''
        ) {
            if (!$this->isPhpDocCompatibleWithSignature($sigReturnType, $docReturnType, $allTemplateNames)) {
                $mismatches[] = "return type: sig '{$sigReturnType}', phpdoc '{$docReturnType}'";
            }
        }

        // Parameters
        foreach ($this->filterAndDeduplicateParamsPhpDoc($method->getParameters(), $phpVersion) as $param) {
            $sigType = $this->getParamSigTypeForPhpDoc($param, $phpVersion);
            $docType = $param->getTypeFromPhpDoc();

            if ($sigType === null || $sigType === '' || $docType === null || $docType === '') {
                continue;
            }

            if (!$this->isPhpDocCompatibleWithSignature($sigType, $docType, $allTemplateNames)) {
                $mismatches[] = "\${$param->getName()}: sig '{$sigType}', phpdoc '{$docType}'";
            }
        }

        return $mismatches;
    }
}
