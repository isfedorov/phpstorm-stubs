<?php

namespace StubTests\Model\ContainerEntitiesManagers;

use StubTests\Model\PHPClass;
use StubTests\Model\Predicats\ClassesFilterPredicateProvider;
use StubTests\Model\RuntimeException;
use StubTests\Parsers\ParserUtils;

class ContainerClassesManager
{
    /**
     * @var PHPClass[]
     */
    private $classes = [];


    /**
     * @return PHPClass[]
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * @param string $id
     * @param string|null $sourceFilePath
     * @param bool $shouldSuitCurrentPhpVersion
     *
     * @return PHPClass|null
     * @throws RuntimeException
     */
    public function getClass($id, $sourceFilePath = null, $shouldSuitCurrentPhpVersion = true)
    {
        if ($this->containsReflectionStubs) {
            $classes = array_filter($this->classes, function (PHPClass $class) use ($id) {
                return $class->fqnBasedId === $id && $class->getOrCreateStubSpecificProperties()->stubObjectHash == null;
            });
        } else {
            $classes = array_filter($this->classes, function (PHPClass $class) use ($shouldSuitCurrentPhpVersion, $id) {
                return $class->fqnBasedId === $id && (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($class));
            });
        }
        if (count($classes) === 1) {
            return array_pop($classes);
        }

        if ($sourceFilePath !== null) {
            $classes = array_filter($classes, function (PHPClass $class) use ($shouldSuitCurrentPhpVersion, $sourceFilePath) {
                return $class->getOrCreateStubSpecificProperties()->sourceFilePath === $sourceFilePath &&
                    (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($class));
            });
        }
        if (count($classes) > 1) {
            throw new RuntimeException("Multiple classes with name $id found");
        }
        if (!empty($classes)) {
            return array_pop($classes);
        }
        return null;
    }

    public function getClassNew($classId, $filterCallback = null)
    {
        if ($filterCallback === null) {
            $filterCallback = ClassesFilterPredicateProvider::getDefaultSuitableFunctions($classId);
        }
        $classes = array_filter($this->classes, $filterCallback);
        if (count($classes) > 1) {
            throw new RuntimeException("Multiple classes with name $classId found");
        }
        if (!empty($classes)) {
            return array_pop($classes);
        }
        return null;
    }


    /**
     * @param true $shouldSuitCurrentLanguageVersion
     * @return PHPClass[]
     */
    public function getCoreClasses($shouldSuitCurrentPhpVersion = true)
    {
        return array_filter($this->classes, function (PHPClass $class) use ($shouldSuitCurrentPhpVersion) {
            return $class->getOrCreateStubSpecificProperties()->stubBelongsToCore === true && (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($class));
        });
    }

    public function addClass(PHPClass $class)
    {
        if (isset($class->fqnBasedId)) {
            if (array_key_exists($class->fqnBasedId, $this->classes)) {
                $amount = count(array_filter(
                    $this->classes,
                    function (PHPClass $nextClass) use ($class) {
                        return $nextClass->fqnBasedId === $class->fqnBasedId;
                    }
                ));
                $this->classes[$class->fqnBasedId . '_duplicated_' . $amount] = $class;
            } else {
                $this->classes[$class->fqnBasedId] = $class;
            }
        }
    }
}