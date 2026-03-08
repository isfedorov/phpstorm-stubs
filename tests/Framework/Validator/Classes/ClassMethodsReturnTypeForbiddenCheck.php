<?php

namespace StubTests\Sources\Validator\Classes;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\AbstractClassCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that overridable stub methods available before PHP 7.0 do not declare
 * a return type hint in their PHP signature.
 *
 * Return type hints were introduced in PHP 7.0. If a public or protected method
 * is available in PHP 5.6 and its stub declares a return type in the actual signature,
 * then child classes written for PHP 5.6 cannot provide a matching override — the
 * `: T` syntax did not exist yet. Private and final methods are excluded because they
 * cannot be overridden.
 *
 * Note: only the actual PHP signature type is checked. LanguageLevelTypeAware attribute
 * values are IDE metadata and do not affect runtime PHP type compatibility.
 *
 * Note: this check covers only return types. Parameter type hints for class names,
 * `array`, and `callable` were already valid in PHP 5.x and are not checked here.
 *
 * The check runs only for PHP 5.6 (the sole version before PHP 7.0 in the test
 * matrix).
 *
 * Known problems are supported at two granularities:
 * - entity-level: EntityType::CLASS_TYPE + classId + 'ReturnTypeForbiddenCheck'
 *   → skips all checks for the entity.
 * - method-level: EntityType::METHOD + '\EntityId::methodName' + 'ReturnTypeForbiddenCheck'
 *   → skips the specific method.
 */
class ClassMethodsReturnTypeForbiddenCheck extends AbstractClassCheck
{

    protected const CHECK_NAME = 'ReturnTypeForbiddenCheck';

    /**
     * Runs only for PHP versions before 7.0 — the range in which return type
     * hints were not yet available. In practice this means PHP 5.6 only.
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
        // Return types on non-overridable methods are not a compatibility concern.
        if (property_exists($entity, 'isFinal') && $entity->isFinal) {
            $results->addSuccess($entityId);
            return $results;
        }

        $stubMethods = $this->collectEntityMethods($entity, $phpVersion);

        $hasMismatch = false;
        foreach ($stubMethods as $methodName => $method) {
            // Only overridable methods matter: child classes cannot override private or final
            // methods, so return type hints on such methods are not a compatibility issue.
            $access = method_exists($method, 'getAccess') ? $method->getAccess() : null;
            if ($access?->toString() === 'private') {
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

            // Only check the actual PHP signature type, not LanguageLevelTypeAware metadata:
            // attribute values are IDE hints and do not affect runtime method override compatibility.
            $signatureType = method_exists($method, 'getReturnTypeFromSignature')
                ? $method->getReturnTypeFromSignature() : null;
            $returnType = ($signatureType !== null && method_exists($signatureType, 'toString'))
                ? $signatureType->toString() : '';
            if ($returnType === '') {
                continue;
            }

            $methodEntityId = $entityId . '::' . $methodName;
            $hasMismatch    = true;

            if (!$this->skipWithKnownProblem($results, EntityType::METHOD->value, $methodEntityId, static::CHECK_NAME, $phpVersion)) {
                $results->addFailure(
                    $methodEntityId,
                    "{$this->getEntityLabel()} method {$methodEntityId} has return type '{$returnType}' " .
                    "but is available before PHP 7.0 (return type hints were introduced in PHP 7.0). " .
                    "Use #[LanguageLevelTypeAware(['7.0' => '...'], default: '')] to restrict the return type to PHP 7.0+."
                );
            }
        }

        if (!$hasMismatch) {
            $results->addSuccess($entityId);
        }

        return $results;
    }
}
