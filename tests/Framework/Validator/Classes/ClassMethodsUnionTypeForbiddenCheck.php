<?php

namespace StubTests\Sources\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\Types\NullableType;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractClassCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that overridable stub methods available before PHP 8.0 do not declare
 * union type hints — neither on their return type nor on any parameter.
 *
 * Union type hints (T1|T2) were introduced in PHP 8.0. If a public or protected
 * method is available in PHP 7.x or earlier and its stub declares a union return
 * type or a union parameter type in the actual PHP signature, then child classes
 * written for those versions cannot provide a matching type-hinted override.
 * Private methods and final methods are excluded because they cannot be overridden.
 *
 * Note: Only signature types are checked. LanguageLevelTypeAware attribute values
 * are IDE metadata and do not affect runtime PHP type compatibility.
 *
 * The nullable shorthand ?T (which serialises as T|null) is intentionally excluded:
 * it was introduced in PHP 7.1 and is therefore valid for PHP 7.1–7.4. Signature
 * NullableType objects are skipped before the union-type string check is applied.
 *
 * The check runs for every PHP version before 8.0 (PHP 5.6 through PHP 7.4).
 *
 * Known problems are supported at two granularities:
 * - entity-level: EntityType::CLASS_TYPE + classId + 'UnionTypeForbiddenCheck'
 *   → skips all checks for the entity.
 * - method-level: EntityType::METHOD + '\EntityId::methodName' + 'UnionTypeForbiddenCheck'
 *   → skips the method (both return type and all parameters).
 */
class ClassMethodsUnionTypeForbiddenCheck extends AbstractClassCheck
{

    protected const CHECK_NAME = 'UnionTypeForbiddenCheck';

    /**
     * Runs for all PHP versions before 8.0 — the range in which union type
     * hints (T1|T2) were not yet available.
     */
    public function supports(string $phpVersion): bool
    {
        return version_compare($phpVersion, '8.0', '<');
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
        // Union type hints on non-overridable methods are not a compatibility concern.
        if (property_exists($entity, 'isFinal') && $entity->isFinal) {
            $results->addSuccess($entityId);
            return $results;
        }

        $stubMethods = $this->collectEntityMethods($entity, $phpVersion);

        $hasMismatch = false;
        foreach ($stubMethods as $methodName => $method) {
            // Only overridable methods matter: child classes cannot override private or final
            // methods, so union type hints on such methods are not a compatibility issue.
            $access = method_exists($method, 'getAccess') ? $method->getAccess() : null;
            if ($access === 'private') {
                continue;
            }
            if (method_exists($method, 'isFinal') && $method->isFinal()) {
                continue;
            }
            // Methods with tentative return types were added as non-enforced hints in PHP 8.1.
            // Subclasses are allowed to omit or change the return type, so there is no
            // compatibility issue for child code written for PHP 5.6–7.4.
            if (method_exists($method, 'hasTentativeReturnType') && $method->hasTentativeReturnType()) {
                continue;
            }

            $methodEntityId = $entityId . '::' . $methodName;
            $issues         = [];

            // ── Check return type ─────────────────────────────────────────────────
            // Only check the actual PHP signature type, not LanguageLevelTypeAware metadata:
            // attributes are IDE hints and do not affect runtime method override compatibility.
            // Skip NullableType (?T) — nullable type hints are valid from PHP 7.1.
            $signatureReturnType = method_exists($method, 'getReturnTypeFromSignature')
                ? $method->getReturnTypeFromSignature() : null;
            if ($signatureReturnType !== null && !($signatureReturnType instanceof NullableType)) {
                $returnType = $signatureReturnType->toString();
                if ($returnType !== '' && $this->isUnionType($returnType)) {
                    $issues[$methodEntityId] =
                        "{$this->getEntityLabel()} method {$methodEntityId} has union return type '{$returnType}' " .
                        "but is available before PHP 8.0 (union type hints were introduced in PHP 8.0). " .
                        "Use #[LanguageLevelTypeAware(['8.0' => 'T1|T2'], default: '...')] to restrict the union hint to PHP 8.0+.";
                }
            }

            // ── Check parameter types ─────────────────────────────────────────────
            // Only check the actual PHP signature type, not LanguageLevelTypeAware metadata:
            // attributes are IDE hints and do not affect runtime method override compatibility.
            $parameters = method_exists($method, 'getParameters') ? $method->getParameters() : [];
            foreach ($this->filterAndDeduplicateParams($parameters, $phpVersion) as $param) {
                $declaredType = $param->getDeclaredType();
                // Skip NullableType (?T) — nullable type hints are valid from PHP 7.1.
                if ($declaredType instanceof NullableType) {
                    continue;
                }
                $paramType = method_exists($declaredType, 'toString') ? $declaredType->toString() : '';
                if ($paramType !== '' && $this->isUnionType($paramType)) {
                    $paramEntityId          = $methodEntityId . '::$' . $param->getName();
                    $issues[$paramEntityId] =
                        "{$this->getEntityLabel()} method {$methodEntityId} parameter \${$param->getName()} " .
                        "has union type hint '{$paramType}' but the method is available before PHP 8.0 " .
                        "(union type hints were introduced in PHP 8.0). " .
                        "Use #[LanguageLevelTypeAware(['8.0' => 'T1|T2'], default: '...')] to restrict the union hint to PHP 8.0+.";
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

    /**
     * Return true when $type represents a union type.
     *
     * A union type contains '|' as a separator between component types.
     * Note: NullableType (?T) is excluded before this method is called,
     * so T|null from a NullableType signature is never passed here.
     */
    private function isUnionType(string $type): bool
    {
        return str_contains($type, '|');
    }
}
