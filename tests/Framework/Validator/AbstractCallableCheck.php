<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;

/**
 * Base class for checks that look up functions or methods by their entity ID.
 *
 * Provides findCallable() which handles both "functionName" and "ClassName::methodName"
 * formats, and correctly selects the version-appropriate overload when multiple
 * function definitions exist for the same ID.
 */
abstract class AbstractCallableCheck extends AbstractReflectionCheck
{
    /**
     * Find a function or method in the given storage.
     *
     * @param string $entityId Format: "functionName" or "ClassName::methodName"
     * @return mixed The function/method object or null if not found
     */
    protected function findCallable(ParsedDataStorageManager $storage, string $entityId, string $phpVersion): mixed
    {
        if (str_contains($entityId, '::')) {
            [$className, $methodName] = explode('::', $entityId, 2);

            foreach ($storage->getClasses() as $class) {
                $classId = method_exists($class, 'getId') ? $class->getId() :
                    (method_exists($class, 'getName') ? $class->getName() : '');

                if ($classId === $className && method_exists($class, 'getMethods')) {
                    foreach ($class->getMethods() as $method) {
                        $name = method_exists($method, 'getName') ? $method->getName() : '';
                        if ($name === $methodName) {
                            return $method;
                        }
                    }
                }
            }
            return null;
        }

        return $this->findVersionedFunction($storage, $entityId, $phpVersion);
    }

    /**
     * Find the version-appropriate function definition from storage.
     *
     * When multiple definitions exist with different #[PhpStormStubsElementAvailable]
     * attributes, returns the one available for the given PHP version.
     *
     * @return mixed The function object or null if not found
     */
    private function findVersionedFunction(ParsedDataStorageManager $storage, string $functionId, string $phpVersion): mixed
    {
        $candidates = [];

        foreach ($storage->getFunctions() as $function) {
            $currentId = method_exists($function, 'getId') ? $function->getId() :
                (method_exists($function, 'getName') ? $function->getName() : '');

            if ($currentId === $functionId) {
                $candidates[] = $function;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        // Multiple candidates — pick the one available for the target PHP version
        foreach ($candidates as $candidate) {
            $sinceVersion   = method_exists($candidate, 'getSinceVersion') ? $candidate->getSinceVersion() : null;
            $removedVersion = method_exists($candidate, 'getRemovedVersion') ? $candidate->getRemovedVersion() : null;

            $isAvailableSince = $sinceVersion === null || version_compare($phpVersion, $sinceVersion, '>=');
            $isNotRemoved     = $removedVersion === null || version_compare($phpVersion, $removedVersion, '<=');

            if ($isAvailableSince && $isNotRemoved) {
                return $candidate;
            }
        }

        return $candidates[0];
    }
}
