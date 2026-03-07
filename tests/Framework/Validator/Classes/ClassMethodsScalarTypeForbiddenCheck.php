<?php

namespace StubTests\Sources\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\Types\NullableType;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractClassCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that overridable stub methods available before PHP 7.0 do not declare
 * scalar parameter type hints (int, float, string, bool).
 *
 * Scalar type hints were introduced in PHP 7.0. If a public or protected method
 * is available in PHP 5.6 and its stub declares a scalar type hint on any parameter
 * in the actual PHP signature, then child classes written for PHP 5.6 cannot
 * provide a matching override. Private and final methods are excluded because they
 * cannot be overridden.
 *
 * Note: only the actual PHP signature type is checked. LanguageLevelTypeAware
 * attribute values are IDE metadata and do not affect runtime PHP type compatibility.
 *
 * Note: this check covers only parameter type hints. Return type hints are already
 * fully covered by ClassMethodsReturnTypeForbiddenCheck (which forbids any return type
 * before PHP 7.0). Parameter type hints for class names, array, and callable were
 * valid in PHP 5.x and are not scalar types, so they are not checked here.
 *
 * The scalars introduced in PHP 7.0 for type hints: int, float, string, bool.
 *
 * The check runs only for PHP 5.6 (the sole version before PHP 7.0 in the test
 * matrix).
 *
 * Known problems are supported at two granularities:
 * - entity-level: EntityType::CLASS_TYPE + classId + 'ScalarTypeForbiddenCheck'
 *   → skips all checks for the entity.
 * - method-level: EntityType::METHOD + '\EntityId::methodName' + 'ScalarTypeForbiddenCheck'
 *   → skips all parameters of the specific method.
 */
class ClassMethodsScalarTypeForbiddenCheck extends AbstractClassCheck
{
    protected const CHECK_NAME = 'ScalarTypeForbiddenCheck';

    /** Scalar parameter type hints introduced in PHP 7.0. */
    private const SCALAR_TYPES = ['int', 'float', 'string', 'bool'];

    /**
     * Runs only for PHP versions before 7.0 — the range in which scalar parameter
     * type hints (int, float, string, bool) were not yet available. In practice
     * this means PHP 5.6 only.
     */
    public function supports(string $phpVersion): bool
    {
        return version_compare($phpVersion, '7.0', '<');
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
        // Scalar parameter types on non-overridable methods are not a compatibility concern.
        if (property_exists($entity, 'isFinal') && $entity->isFinal) {
            $results->addSuccess($entityId);
            return $results;
        }

        $stubMethods = $this->collectEntityMethods($entity, $phpVersion);

        $hasMismatch = false;
        foreach ($stubMethods as $methodName => $method) {
            // Only overridable methods matter: child classes cannot override private or final
            // methods, so scalar parameter type hints on such methods are not a compatibility issue.
            $access = method_exists($method, 'getAccess') ? $method->getAccess() : null;
            if ($access === 'private') {
                continue;
            }
            if (method_exists($method, 'isFinal') && $method->isFinal()) {
                continue;
            }
            // Methods with tentative return types were added as non-enforced hints in PHP 8.1.
            // Subclasses are allowed to omit the return type, so there is no compatibility
            // issue for child code written for PHP 5.6.
            if (method_exists($method, 'hasTentativeReturnType') && $method->hasTentativeReturnType()) {
                continue;
            }

            $methodEntityId = $entityId . '::' . $methodName;
            $issues         = [];

            // ── Check parameter types ─────────────────────────────────────────────
            // Only check the actual PHP signature type, not LanguageLevelTypeAware metadata:
            // attribute values are IDE hints and do not affect runtime method override compatibility.
            $parameters = method_exists($method, 'getParameters') ? $method->getParameters() : [];
            foreach ($this->filterAndDeduplicateParams($parameters, $phpVersion) as $param) {
                $declaredType = $param->getDeclaredType();
                // For NullableType (?T), extract the inner scalar to check it.
                $scalarCandidate = $declaredType instanceof NullableType
                    ? $this->extractNullableInnerType($declaredType)
                    : ($declaredType instanceof StandaloneType ? $declaredType->toString() : '');
                if ($scalarCandidate !== '' && $this->isScalarType($scalarCandidate)) {
                    $paramType               = $declaredType->toString();
                    $paramEntityId           = $methodEntityId . '::$' . $param->getName();
                    $issues[$paramEntityId]  =
                        "{$this->getEntityLabel()} method {$methodEntityId} parameter \${$param->getName()} " .
                        "has scalar type hint '{$paramType}' but the method is available before PHP 7.0 " .
                        "(scalar type hints were introduced in PHP 7.0). " .
                        "Use #[LanguageLevelTypeAware(['7.0' => '...'], default: '')] to restrict the scalar hint to PHP 7.0+.";
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
     * Return true when $typeName is one of the scalar parameter types introduced in PHP 7.0.
     */
    private function isScalarType(string $typeName): bool
    {
        return in_array($typeName, self::SCALAR_TYPES, true);
    }

    /**
     * Extract the inner type name from a NullableType (?T).
     *
     * NullableType::toString() returns 'T|null'. Here we need the bare 'T' to check whether
     * it is a scalar type. We use toArray() which returns ['T', 'null'].
     */
    private function extractNullableInnerType(NullableType $type): string
    {
        foreach ($type->toArray() as $part) {
            if ($part !== 'null') {
                return $part;
            }
        }
        return '';
    }
}
