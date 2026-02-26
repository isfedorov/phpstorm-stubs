<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ClassInterfaceFqnsExtractor;
use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\KnownProblems\EntityType;

class ClassInterfacesCheck extends AbstractClassCheck
{
    private ClassInterfaceFqnsExtractor $fqnsExtractor;

    public function __construct(
        ?ReflectionProviderInterface $reflectionProvider = null,
        ?KnownProblemsRegistry $knownProblemsRegistry = null,
        ?ClassInterfaceFqnsExtractor $fqnsExtractor = null
    ) {
        parent::__construct($reflectionProvider, $knownProblemsRegistry);
        $this->fqnsExtractor = $fqnsExtractor ?? new ClassInterfaceFqnsExtractor();
    }

    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        if ($this->skipWithKnownProblem($results, EntityType::CLASS_TYPE->value, $entityId, 'ClassInterfacesCheck', $phpVersion)) {
            return $results;
        }

        $reflection = $this->reflectionProvider->getReflection($phpVersion);

        $reflectionClass = $this->findClassById($reflection, $entityId);
        if ($reflectionClass === null) {
            $results->addFailure($entityId, "Class {$entityId} not found in reflection data");
            return $results;
        }

        $stubClass = $this->findClassById($stubs, $entityId);
        if ($stubClass === null) {
            $results->addFailure($entityId, "Class {$entityId} not found in stubs");
            return $results;
        }

        // PHP reflection reports ALL interfaces (including transitively inherited ones via both
        // parent classes and interface inheritance). Stubs only declare the interfaces that
        // a class introduces directly in its `implements` clause.
        //
        // We only check that stub-declared interfaces actually appear in reflection's full list.
        // The reverse (checking that every reflection interface appears in stubs) is not done
        // here because PHP reflection includes transitively inherited interfaces (e.g. Traversable
        // via Iterator) that stubs correctly omit from the `implements` clause.
        $reflectionAllIfaces = $this->fqnsExtractor->extract($reflectionClass);
        $stubIfaces = $this->fqnsExtractor->extract($stubClass);

        // Stubs should not declare interfaces absent from reflection's full list
        $spurious = array_diff($stubIfaces, $reflectionAllIfaces);
        if (!empty($spurious)) {
            sort($spurious);
            $results->addFailure(
                $entityId,
                "Interface mismatch for {$entityId}: stubs declare interface(s) not in reflection: " .
                implode(', ', $spurious)
            );
            return $results;
        }

        $results->addSuccess($entityId);
        return $results;
    }
}
