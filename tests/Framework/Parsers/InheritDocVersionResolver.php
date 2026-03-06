<?php

namespace StubTests\Sources\Parsers;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClassLikeObject;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;

/**
 * Resolves sinceVersion for methods whose PhpDoc contains {@inheritDoc} by
 * inheriting the version from the nearest parent interface or class that defines
 * the same method with an explicit @since tag.
 *
 * Must be called AFTER ClassHierarchyResolver has wired up the object references
 * in PHPClass::$interfaces, PHPClass::$parentClass, and PHPInterface::$parentInterfaces.
 */
class InheritDocVersionResolver
{
    /**
     * @param iterable $classes    All PHPClass instances from storage
     * @param iterable $interfaces All PHPInterface instances from storage
     * @param iterable $enums      All PHPEnum instances from storage
     */
    public function resolve(iterable $classes, iterable $interfaces = [], iterable $enums = []): void
    {
        foreach ($interfaces as $interface) {
            $this->resolveClassLike($interface);
        }

        foreach ($classes as $class) {
            $this->resolveClassLike($class);
        }

        foreach ($enums as $enum) {
            $this->resolveClassLike($enum);
        }
    }

    private function resolveClassLike(PHPClassLikeObject $classLike): void
    {
        foreach ($classLike->getMethods() as $method) {
            if ($method->getSinceVersion() !== null) {
                continue;
            }

            $phpDoc = $method->getPhpDoc();
            if ($phpDoc === null || !$this->hasInheritDoc($phpDoc)) {
                continue;
            }

            $sinceVersion = $this->findVersionInParents($method->getName(), $classLike, []);
            if ($sinceVersion !== null) {
                $method->setSinceVersion($sinceVersion);
            }
        }
    }

    private function hasInheritDoc(string $phpDoc): bool
    {
        return str_contains(strtolower($phpDoc), '@inheritdoc');
    }

    private function findVersionInParents(string $methodName, PHPClassLikeObject $classLike, array $visited): ?string
    {
        // Check implemented interfaces (available on PHPClass and PHPEnum)
        if (method_exists($classLike, 'getImplementedInterfaces')) {
            foreach ($classLike->getImplementedInterfaces() as $interface) {
                $version = $this->findVersionInInterface($methodName, $interface, $visited);
                if ($version !== null) {
                    return $version;
                }
            }
        }

        // Check parent interfaces (available on PHPInterface)
        if ($classLike instanceof PHPInterface) {
            foreach ($classLike->getParentInterfaces() as $parentInterface) {
                $version = $this->findVersionInInterface($methodName, $parentInterface, $visited);
                if ($version !== null) {
                    return $version;
                }
            }
        }

        // Check parent class chain (available on PHPClass)
        if ($classLike instanceof PHPClass && $classLike->getParentClass() !== null) {
            $version = $this->findVersionInClass($methodName, $classLike->getParentClass(), $visited);
            if ($version !== null) {
                return $version;
            }
        }

        return null;
    }

    private function findVersionInInterface(string $methodName, PHPInterface $interface, array $visited): ?string
    {
        $id = $interface->getId();
        if ($id !== null) {
            if (in_array($id, $visited, true)) {
                return null;
            }
            $visited[] = $id;
        }

        foreach ($interface->getMethods() as $method) {
            if ($method->getName() === $methodName && $method->getSinceVersion() !== null) {
                return $method->getSinceVersion();
            }
        }

        foreach ($interface->getParentInterfaces() as $parent) {
            $version = $this->findVersionInInterface($methodName, $parent, $visited);
            if ($version !== null) {
                return $version;
            }
        }

        return null;
    }

    private function findVersionInClass(string $methodName, PHPClass $class, array $visited): ?string
    {
        $id = $class->getId();
        if ($id !== null) {
            if (in_array($id, $visited, true)) {
                return null;
            }
            $visited[] = $id;
        }

        foreach ($class->getMethods() as $method) {
            if ($method->getName() === $methodName && $method->getSinceVersion() !== null) {
                return $method->getSinceVersion();
            }
        }

        foreach ($class->getImplementedInterfaces() as $interface) {
            $version = $this->findVersionInInterface($methodName, $interface, $visited);
            if ($version !== null) {
                return $version;
            }
        }

        if ($class->getParentClass() !== null) {
            return $this->findVersionInClass($methodName, $class->getParentClass(), $visited);
        }

        return null;
    }
}
