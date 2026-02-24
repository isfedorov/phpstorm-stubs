<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ClassAncestorNamesExtractor;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\KnownProblems\EntityType;

class ClassParentClassCheck implements CheckInterface
{
    private ReflectionProviderInterface $reflectionProvider;
    private KnownProblemsRegistry $knownProblemsRegistry;
    private ClassAncestorNamesExtractor $ancestorExtractor;

    public function __construct(
        ?ReflectionProviderInterface $reflectionProvider = null,
        ?KnownProblemsRegistry $knownProblemsRegistry = null,
        ?ClassAncestorNamesExtractor $ancestorExtractor = null
    ) {
        $this->reflectionProvider = $reflectionProvider ?? new RunnerReflectionProvider();
        $this->knownProblemsRegistry = $knownProblemsRegistry ?? KnownProblemsRegistry::getInstance();
        $this->ancestorExtractor = $ancestorExtractor ?? new ClassAncestorNamesExtractor();
    }

    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        // Check if this entity has a known problem that should skip validation
        if ($this->knownProblemsRegistry->shouldSkipValidation(
            EntityType::CLASS_TYPE->value,
            $entityId,
            'ClassParentClassCheck',
            $phpVersion
        )) {
            $reason = $this->knownProblemsRegistry->getSkipReason(
                EntityType::CLASS_TYPE->value,
                $entityId,
                'ClassParentClassCheck',
                $phpVersion
            );
            $results->addSuccess($entityId . ' (skipped: ' . $reason . ')');
            return $results;
        }

        // Get reflection data for this PHP version
        $reflection = $this->reflectionProvider->getReflection($phpVersion);

        // Find class in reflection
        $reflectionClass = null;
        foreach ($reflection->getClasses() as $class) {
            if ($class->getId() === $entityId) {
                $reflectionClass = $class;
                break;
            }
        }

        if ($reflectionClass === null) {
            $results->addFailure($entityId, "Class {$entityId} not found in reflection data");
            return $results;
        }

        // Find class in stubs
        $stubClass = null;
        foreach ($stubs->getClasses() as $class) {
            if ($class->getId() === $entityId) {
                $stubClass = $class;
                break;
            }
        }

        if ($stubClass === null) {
            $results->addFailure($entityId, "Class {$entityId} not found in stubs");
            return $results;
        }

        // Get parent class FQN reported by reflection.
        // After ClassHierarchyResolver links the parent stub to the actual PHPClass object,
        // getName() returns only the short name (e.g. "RandomError"), while getId() carries
        // the full namespace (e.g. "\Random\RandomError"). We strip the leading \ to match
        // the format that getAncestorClassNames() returns for the stubs side.
        // If the parent stub is unlinked (getId() is null), we fall back to getName() which
        // holds the original stored name from the cache (already FQN for reflection data).
        $reflectionParentObj = $reflectionClass->parentClass;
        if ($reflectionParentObj === null) {
            $reflectionParentName = null;
        } else {
            $id = $reflectionParentObj->getId();
            $reflectionParentName = $id !== null ? ltrim($id, '\\') : $reflectionParentObj->getName();
        }

        // Both have no parent — valid
        if ($reflectionParentName === null && $stubClass->parentClass === null) {
            $results->addSuccess($entityId);
            return $results;
        }

        // Reflection has no parent but stubs declare one — mismatch
        if ($reflectionParentName === null) {
            $stubDirectParentName = $stubClass->parentClass->getName();
            $results->addFailure(
                $entityId,
                "Parent class mismatch for {$entityId}: reflection has no parent, stubs have '{$stubDirectParentName}'"
            );
            return $results;
        }

        // Check whether the reflection parent appears anywhere in the stubs' full ancestor chain.
        // ClassHierarchyResolver ensures that parentClass references are linked to the actual
        // PHPClass objects, so the extractor can traverse the complete hierarchy
        // (e.g. ParseError → CompileError → Error) without any additional lookups here.
        $stubAncestors = $this->ancestorExtractor->extract($stubClass);
        if (in_array($reflectionParentName, $stubAncestors, true)) {
            $results->addSuccess($entityId);
            return $results;
        }

        $stubDirectParentName = $stubClass->parentClass !== null
            ? $stubClass->parentClass->getName()
            : '(none)';

        $results->addFailure(
            $entityId,
            "Parent class mismatch for {$entityId}: reflection has '{$reflectionParentName}', " .
            "stubs have '{$stubDirectParentName}'"
        );

        return $results;
    }
}
