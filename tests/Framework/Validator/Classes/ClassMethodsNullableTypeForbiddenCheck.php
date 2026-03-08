<?php

namespace StubTests\Sources\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\Types\NullableType;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractClassCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that overridable stub methods available before PHP 7.1 do not declare
 * nullable type hints — neither on their return type nor on any parameter.
 *
 * Nullable type hints (?T) were introduced in PHP 7.1.  If a public or protected
 * method is available in PHP 7.0 or earlier and its stub declares a nullable return
 * type or a nullable parameter type, then child classes written for PHP 5.6/7.0
 * cannot provide a matching type-hinted override — the `?T` syntax did not exist yet.
 * Private methods and final methods are excluded because they cannot be overridden.
 *
 * Note: only the actual PHP signature type is checked. LanguageLevelTypeAware
 * attribute values are IDE metadata and do not affect runtime PHP type compatibility.
 *
 * The check runs for every PHP version before 7.1 (PHP 5.6 and PHP 7.0).
 *
 * Known problems are supported at two granularities:
 * - entity-level: EntityType::CLASS_TYPE + classId + 'NullableTypeForbiddenCheck'
 *   → skips all checks for the entity.
 * - method-level: EntityType::METHOD + '\EntityId::methodName' + 'NullableTypeForbiddenCheck'
 *   → skips the method (both return type and all parameters).
 */
class ClassMethodsNullableTypeForbiddenCheck extends AbstractClassCheck
{
    protected const CHECK_NAME = 'NullableTypeForbiddenCheck';

    /**
     * Runs for all PHP versions before 7.1 — the range in which nullable type
     * hints (?T) were not yet available.
     *
     * Running for both PHP 5.6 and PHP 7.0 ensures that entities only present in
     * PHP 5.6 (removed before PHP 7.0) are also covered.
     */
    public function supports(string $phpVersion): bool
    {
        return version_compare($phpVersion, '7.1', '<');
    }

    /**
     * Template method: look up the entity in the given storage by ID.
     * Override in subclasses to support interfaces / enums.
     */
    protected function findEntity(ParsedDataStorageManager $stubs, string $entityId): mixed
    {
        return $this->findClassById($stubs, $entityId);
    }

    /**
     * Template method: collect version-available stub methods for the entity.
     * Override in subclasses to traverse interface / enum hierarchies.
     *
     * @return array<string, \StubTests\Sources\Parsers\Entities\Model\PHPMethod>
     */
    protected function collectEntityMethods(mixed $entity, string $phpVersion): array
    {
        return $this->collectVersionedStubMethods($entity, $phpVersion);
    }

    /**
     * Template method: entity type used for known-problem lookups.
     * Override to return EntityType::INTERFACE_TYPE / ENUM_TYPE in subclasses.
     */
    protected function getEntityType(): string
    {
        return EntityType::CLASS_TYPE->value;
    }

    /**
     * Template method: label used in error messages.
     * Override to return "Interface" / "Enum" in subclasses.
     */
    protected function getEntityLabel(): string
    {
        return 'Class';
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, $this->getEntityType(), $entityId, static::CHECK_NAME, $phpVersion)) {
            return $results;
        }

        $entity = $this->findEntity($stubs, $entityId);
        if ($entity === null) {
            // Entity may not exist in stubs for this version — not this check's responsibility.
            $results->addSuccess($entityId);
            return $results;
        }

        // Final classes cannot be extended, so no child class can ever override their methods.
        // Nullable return types on non-overridable methods are not a compatibility concern.
        if (property_exists($entity, 'isFinal') && $entity->isFinal) {
            $results->addSuccess($entityId);
            return $results;
        }

        $stubMethods = $this->collectEntityMethods($entity, $phpVersion);

        $hasMismatch = false;
        foreach ($stubMethods as $methodName => $method) {
            // Only overridable methods matter: child classes cannot override private or final
            // methods, so nullable type hints on such methods are not a compatibility issue.
            $access = method_exists($method, 'getAccess') ? $method->getAccess() : null;
            if ($access?->toString() === 'private') {
                continue;
            }
            if (method_exists($method, 'isFinal') && $method->isFinal()) {
                continue;
            }
            // Methods with tentative return types were added as non-enforced hints in PHP 8.1.
            // Subclasses are allowed to omit or change the return type, so there is no
            // compatibility issue for child code written for PHP 5.6/7.0.
            if (method_exists($method, 'hasTentativeReturnType') && $method->hasTentativeReturnType()) {
                continue;
            }

            $methodEntityId = $entityId . '::' . $methodName;
            $issues         = [];

            // ── Check return type ─────────────────────────────────────────────────
            // Only check the actual PHP signature type, not LanguageLevelTypeAware metadata:
            // attribute values are IDE hints and do not affect runtime method override compatibility.
            $signatureReturnType = method_exists($method, 'getReturnTypeFromSignature')
                ? $method->getReturnTypeFromSignature() : null;
            if ($signatureReturnType instanceof NullableType) {
                $returnType = $signatureReturnType->toString(); // format: T|null
                $issues[$methodEntityId] =
                    "{$this->getEntityLabel()} method {$methodEntityId} has nullable return type '{$returnType}' " .
                    "but is available before PHP 7.1 (nullable type hints were introduced in PHP 7.1). " .
                    "Use #[LanguageLevelTypeAware(['7.1' => '?...'], default: '...')] to restrict the nullable hint to PHP 7.1+.";
            }

            // ── Check parameter types ─────────────────────────────────────────────
            // Only check the actual PHP signature type, not LanguageLevelTypeAware metadata.
            $parameters = method_exists($method, 'getParameters') ? $method->getParameters() : [];
            foreach ($this->filterAndDeduplicateParams($parameters, $phpVersion) as $param) {
                $declaredType = $param->getDeclaredType();
                if ($declaredType instanceof NullableType) {
                    $paramType               = $declaredType->toString(); // format: T|null
                    $paramEntityId           = $methodEntityId . '::$' . $param->getName();
                    $issues[$paramEntityId]  =
                        "{$this->getEntityLabel()} method {$methodEntityId} parameter \${$param->getName()} " .
                        "has nullable type hint '{$paramType}' but the method is available before PHP 7.1 " .
                        "(nullable type hints were introduced in PHP 7.1). " .
                        "Use #[LanguageLevelTypeAware(['7.1' => '?...'], default: '...')] to restrict the nullable hint to PHP 7.1+.";
                }
            }

            if (empty($issues)) {
                continue;
            }

            $hasMismatch = true;
            if (!$this->skipWithKnownProblem($results, EntityType::METHOD->value, $methodEntityId, static::CHECK_NAME, $phpVersion)) {
                foreach ($issues as $issueId => $issueMessage) {
                    $results->addFailure($issueId, $issueMessage);
                }
            }
        }

        if (!$hasMismatch) {
            $results->addSuccess($entityId);
        }

        return $results;
    }
}
