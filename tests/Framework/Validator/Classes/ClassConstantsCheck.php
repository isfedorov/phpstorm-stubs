<?php

namespace StubTests\Sources\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPClassLikeObject;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\AbstractClassCheck;
use StubTests\Sources\Validator\CheckResultSet;
use StubTests\Sources\Validator\KnownProblems\EntityType;

/**
 * Validates that constants declared in class stubs are consistent with reflection.
 *
 * PHP reflection reports ALL constants of a class including those inherited via the
 * parent-class chain and implemented interfaces.  Stubs only declare constants that are
 * directly introduced (not inherited), so we cannot simply require every reflection
 * constant to appear in the stub.
 *
 * Instead, the check follows the same direction as ClassInterfacesCheck:
 *  - For each constant declared directly in the stub, verify it appears in
 *    reflection's full constant list (which includes all inherited ones).
 *  - Verify visibility matches.
 *  - Verify value matches when both sides provide a non-null scalar value.
 *
 * This catches:
 *  - Wrong values in stubs (e.g. a constant whose numeric value changed in a PHP release).
 *  - Wrong visibility in stubs.
 *  - Spurious constants declared in stubs that don't exist anywhere in reflection.
 *
 * InterfaceConstantsCheck and EnumConstantsCheck extend this class and override
 * the entity-lookup and label methods.
 *
 * Known problems are supported at two levels:
 *  - Entity-level: EntityType::CLASS_TYPE + classId + 'ClassConstantsCheck' → skips entire class.
 *  - Constant-level: EntityType::CLASS_CONSTANT + 'ClassName::CONST' + 'ClassConstantsCheck' → skips one constant.
 */
class ClassConstantsCheck extends AbstractClassCheck
{
    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, $this->getEntityType(), $entityId, $this->getCheckName(), $phpVersion)) {
            return $results;
        }

        $reflection = $this->reflectionProvider->getReflection($phpVersion);

        $reflEntity = $this->findEntity($reflection, $entityId);
        if ($reflEntity === null) {
            $results->addFailure($entityId, "{$this->getEntityLabel()} {$entityId} not found in reflection data");
            return $results;
        }

        $stubEntity = $this->findEntity($stubs, $entityId);
        if ($stubEntity === null) {
            $results->addFailure($entityId, "{$this->getEntityLabel()} {$entityId} not found in stubs");
            return $results;
        }

        // Build name → PHPClassConstant map from reflection (includes all inherited constants)
        $reflMap = [];
        foreach ($reflEntity->getConstants() as $constant) {
            $reflMap[$constant->getName()] = $constant;
        }

        // Validate each constant that is directly declared in the stub and available in this PHP version
        $hasFailures = false;
        foreach ($stubEntity->getConstants() as $stubConstant) {
            $sinceVersion   = $stubConstant->getSinceVersion();
            $removedVersion = $stubConstant->getRemovedVersion();
            $available = ($sinceVersion === null || version_compare($phpVersion, $sinceVersion, '>='))
                && ($removedVersion === null || version_compare($phpVersion, $removedVersion, '<'));
            if (!$available) {
                continue;
            }

            $name = $stubConstant->getName();
            $constantId = "{$entityId}::{$name}";

            if ($this->skipWithKnownProblem($results, $this->getConstantEntityType(), $constantId, $this->getCheckName(), $phpVersion)) {
                continue;
            }

            if (!isset($reflMap[$name])) {
                $results->addFailure(
                    $constantId,
                    "Constant '{$name}' is declared in stubs for {$this->getEntityLabel()} {$entityId} but not found in reflection"
                );
                $hasFailures = true;
                continue;
            }

            $mismatch = $this->compareConstant($reflMap[$name], $stubConstant, $entityId, $name, $phpVersion);
            if ($mismatch !== null) {
                $results->addFailure($constantId, $mismatch);
                $hasFailures = true;
            }
        }

        if (!$hasFailures) {
            $results->addSuccess($entityId);
        }

        return $results;
    }

    /**
     * Locate the entity in the given storage.
     * Subclasses override this to look up interfaces or enums instead.
     */
    protected function findEntity(ParsedDataStorageManager $storage, string $entityId): ?PHPClassLikeObject
    {
        return $this->findClassById($storage, $entityId);
    }

    protected function getEntityLabel(): string
    {
        return 'Class';
    }

    protected function getEntityType(): string
    {
        return EntityType::CLASS_TYPE->value;
    }

    protected function getCheckName(): string
    {
        return 'ClassConstantsCheck';
    }

    /**
     * Entity type used for per-constant known problem lookups.
     * Subclasses override this to return the appropriate constant entity type.
     */
    protected function getConstantEntityType(): string
    {
        return EntityType::CLASS_CONSTANT->value;
    }

    private function compareConstant(
        PHPClassConstant $refl,
        PHPClassConstant $stub,
        string $entityId,
        string $name,
        string $phpVersion
    ): ?string {
        if ($refl->visibility !== $stub->visibility) {
            return "Visibility mismatch for constant '{$entityId}::{$name}': "
                . "reflection='{$refl->visibility}', stub='{$stub->visibility}'";
        }

        // Constant values change between PHP versions; only compare against the latest PHP reflection
        // to avoid false positives from historical value changes.
        if ($phpVersion === PhpVersions::LATEST->value
            && $refl->value !== null && $stub->value !== null
            && (string) $refl->value !== (string) $stub->value
        ) {
            return "Value mismatch for constant '{$entityId}::{$name}': "
                . "reflection='{$refl->value}', stub='{$stub->value}'";
        }

        return null;
    }
}
