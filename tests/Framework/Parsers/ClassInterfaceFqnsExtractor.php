<?php

namespace StubTests\Sources\Parsers;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;

/**
 * Extracts the list of directly-implemented interface FQNs from a PHPClass.
 *
 * When ClassHierarchyResolver has linked interface objects to their actual instances,
 * getId() carries the fully-qualified name (e.g. "\Throwable"). The leading backslash
 * is stripped so the result matches the format PHP reflection returns (e.g. "Throwable").
 * For unresolved stubs (getId() is null) the stored getName() value is used as a fallback.
 */
class ClassInterfaceFqnsExtractor
{
    /**
     * Returns FQNs (no leading \) of all directly-implemented interfaces.
     *
     * @return string[] Interface FQNs
     */
    public function extract(PHPClass $class): array
    {
        $fqns = [];
        foreach ($class->getImplementedInterfaces() as $interface) {
            $id = $interface->getId();
            $name = $id !== null ? ltrim($id, '\\') : $interface->getName();
            if ($name !== null && $name !== '') {
                $fqns[] = $name;
            }
        }
        return $fqns;
    }
}
