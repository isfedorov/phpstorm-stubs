<?php

namespace StubTests\Model;

use RuntimeException;
use StubTests\Model\Predicats\ClassesFilterPredicateProvider;
use StubTests\Model\Predicats\ConstantsFilterPredicateProvider;
use StubTests\Model\Predicats\FunctionsFilterPredicateProvider;
use StubTests\Parsers\ParserUtils;
use function array_key_exists;
use function count;

class StubsContainer
{

    private $containsReflectionStubs;

    /**
     * @var PHPConstant[]
     */
    private $constants = [];

    /**
     * @var PHPFunction[]
     */
    private $functions = [];

    /**
     * @var PHPClass[]
     */
    private $classes = [];

    /**
     * @var PHPInterface[]
     */
    private $interfaces = [];

    /**
     * @var PHPEnum[]
     */
    private $enums = [];

    public function __construct($forReflectionStubs)
    {
        $this->containsReflectionStubs = $forReflectionStubs;
    }

    /**
     * @return PHPConstant[]
     */
    public function getConstants()
    {
        return $this->constants;
    }

    public function getConstant($constantId, $filterCallback = null)
    {
        if ($filterCallback === null) {
            $filterCallback = ConstantsFilterPredicateProvider::getDefaultSuitableConstants($constantId);
        }
        $constants = array_filter($this->constants, $filterCallback);
        if (count($constants) > 1) {
            throw new RuntimeException("Multiple constants with name $constantId found");
        }
        if (!empty($constants)) {
            return array_pop($constants);
        }
        return null;
    }

    public function addConstant(PHPConstant|PHPDefineConstant $constant)
    {
        if (!isset($constant->name)) {
            throw new RuntimeException("Constant name is not set");
        }
        if ($this->constantExists($constant)) {
            $this->addDuplicateConstant($constant);
        } else {
            $this->constants[$constant->fqnBasedId] = $constant;
        }
    }

    private function constantExists(PHPConstant|PHPDefineConstant $constant)
    {
        return array_key_exists($constant->fqnBasedId, $this->constants);
    }

    private function addDuplicateConstant(PHPConstant|PHPDefineConstant $constant)
    {
        $duplicateCount = $this->getDuplicateCount($constant->fqnBasedId);
        $duplicateConstantId = $constant->fqnBasedId . '_duplicated_' . $duplicateCount;
        $this->constants[$duplicateConstantId] = $constant;
    }

    private function getDuplicateCount($fqnBasedId)
    {
        return count(array_filter($this->constants, function (PHPConstant|PHPDefineConstant $existingConstant) use ($fqnBasedId) {
            return $existingConstant->fqnBasedId === $fqnBasedId;
        }));
    }

    /**
     * @return PHPFunction[]
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    public function getFunction($functionId, $filterCallback = null)
    {
        if ($filterCallback === null) {
            $filterCallback = FunctionsFilterPredicateProvider::getDefaultSuitableFunctions($functionId);
        }
        $functions = array_filter($this->functions, $filterCallback);
        if (count($functions) > 1) {
            throw new RuntimeException("Multiple functions with name $functionId found");
        }
        if (!empty($functions)) {
            return array_pop($functions);
        }
        return null;
    }

    public function addFunction(PHPFunction $function)
    {
        if (isset($function->fqnBasedId)) {
            $duplicatedFunctions = $this->getFilteredDuplicatedFunctions($function);
            if (array_key_exists($function->fqnBasedId, $this->functions)) {
                $amount = count($duplicatedFunctions);
                $this->functions[$function->fqnBasedId . '_duplicated_' . $amount] = $function;
                if (!empty($duplicatedFunctions)) {
                    $function->getOrCreateStubSpecificProperties()->duplicateOtherElement = true;
                }
            } else {
                $this->functions[$function->fqnBasedId] = $function;
            }
        }
    }

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
     * @param string $id
     * @param string|null $sourceFilePath
     * @param bool $shouldSuitCurrentPhpVersion
     * @param true $useFQNAsName
     * @param false $fromReflection
     *
     * @return PHPEnum|null
     * @throws RuntimeException
     */
    public function getEnum($id, $sourceFilePath = null, $shouldSuitCurrentPhpVersion = true, $fromReflection = false)
    {
        $enums = array_filter($this->enums, function (PHPEnum $enum) use ($shouldSuitCurrentPhpVersion, $id) {
            return $enum->fqnBasedId === $id && (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($enum));
        });
        if (count($enums) === 1) {
            return array_pop($enums);
        }

        if ($sourceFilePath !== null) {
            $enums = array_filter($enums, function (PHPEnum $enum) use ($shouldSuitCurrentPhpVersion, $sourceFilePath) {
                return $enum->getOrCreateStubSpecificProperties()->sourceFilePath === $sourceFilePath &&
                    (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($enum));
            });
        }
        if (count($enums) > 1) {
            throw new RuntimeException("Multiple enums with name $id found");
        }
        if (!empty($enums)) {
            return array_pop($enums);
        }
        return null;
    }

    public function getEnumByHash(string $hash)
    {
        $enums = array_filter($this->enums, function (PHPEnum $class) use ($hash) {
            return $class->getOrCreateStubSpecificProperties()->stubObjectHash === $hash;
        });
        if (count($enums) > 1) {
            throw new RuntimeException("Multiple enums with name $hash found");
        }
        return array_pop($enums);
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

    /**
     * @param string $id
     * @param string|null $sourceFilePath
     * @param bool $shouldSuitCurrentPhpVersion
     * @param false $fromReflection
     *
     * @return PHPInterface|null
     * @throws RuntimeException
     */
    public function getInterface($id, $sourceFilePath = null, $shouldSuitCurrentPhpVersion = true, $fromReflection = false)
    {
        if ($fromReflection) {
            $interfaces = array_filter($this->interfaces, function (PHPInterface $interface) use ($id) {
                return $interface->fqnBasedId === $id && $interface->getOrCreateStubSpecificProperties()->stubObjectHash == null;
            });
        } else {
            $interfaces = array_filter($this->interfaces, function (PHPInterface $interface) use ($shouldSuitCurrentPhpVersion, $id) {
                return $interface->fqnBasedId === $id && (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($interface));
            });
        }
        if (count($interfaces) === 1) {
            return array_pop($interfaces);
        }

        if ($sourceFilePath !== null) {
            $interfaces = array_filter($interfaces, function (PHPInterface $interface) use ($shouldSuitCurrentPhpVersion, $sourceFilePath) {
                return $interface->getOrCreateStubSpecificProperties()->sourceFilePath === $sourceFilePath &&
                    (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($interface));
            });
        }
        if (count($interfaces) > 1) {
            throw new RuntimeException("Multiple interfaces with name $id found");
        }
        if (!empty($interfaces)) {
            return array_pop($interfaces);
        }
        return null;
    }

    public function getInterfaceByHash(string $hash)
    {
        $interfaces = array_filter($this->interfaces, function (PHPInterface $class) use ($hash) {
            return $class->getOrCreateStubSpecificProperties()->stubObjectHash === $hash;
        });
        if (count($interfaces) > 1) {
            throw new RuntimeException("Multiple interfaces with hash $hash found");
        }
        return array_pop($interfaces);
    }

    /**
     * @return PHPInterface[]
     */
    public function getInterfaces()
    {
        return $this->interfaces;
    }

    /**
     * @return PHPEnum[]
     */
    public function getEnums()
    {
        return $this->enums;
    }

    /**
     * @return PHPInterface[]
     */
    public function getCoreInterfaces($shouldSuitCurrentPhpVersion = true)
    {
        return array_filter($this->interfaces, function (PHPInterface $interface) use ($shouldSuitCurrentPhpVersion) {
            return $interface->getOrCreateStubSpecificProperties()->stubBelongsToCore === true && (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($interface));
        });
    }

    public function getCoreEnums($shouldSuitCurrentPhpVersion = true)
    {
        return array_filter($this->enums, function (PHPEnum $enum) use ($shouldSuitCurrentPhpVersion) {
            return $enum->getOrCreateStubSpecificProperties()->stubBelongsToCore === true && (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($enum));
        });
    }

    public function addInterface(PHPInterface $interface)
    {
        if (isset($interface->fqnBasedId)) {
            if (array_key_exists($interface->fqnBasedId, $this->interfaces)) {
                $amount = count(array_filter(
                    $this->interfaces,
                    function (PHPInterface $nextInterface) use ($interface) {
                        return $nextInterface->fqnBasedId === $interface->fqnBasedId;
                    }
                ));
                $this->interfaces[$interface->fqnBasedId . '_duplicated_' . $amount] = $interface;
            } else {
                $this->interfaces[$interface->fqnBasedId] = $interface;
            }
        }
    }

    public function addEnum(PHPEnum $enum)
    {
        if (isset($enum->fqnBasedId)) {
            if (array_key_exists($enum->fqnBasedId, $this->enums)) {
                $amount = count(array_filter(
                    $this->enums,
                    function (PHPEnum $nextEnum) use ($enum) {
                        return $nextEnum->fqnBasedId === $enum->fqnBasedId;
                    }
                ));
                $this->enums[$enum->fqnBasedId . '_duplicated_' . $amount] = $enum;
            } else {
                $this->enums[$enum->fqnBasedId] = $enum;
            }
        }
    }

    /**
     * @param PHPFunction $function
     * @return PHPFunction[]
     */
    public function getFilteredDuplicatedFunctions(PHPFunction $function): array
    {
        return array_map(function (PHPFunction $function) {
            $function->getOrCreateStubSpecificProperties()->duplicateOtherElement = true;
            return $function;
        }, array_filter(
            $this->functions,
            function (PHPFunction $nextFunction) use ($function) {
                return $nextFunction->fqnBasedId === $function->fqnBasedId &&
                    array_intersect(ParserUtils::getAvailableInVersions($function), ParserUtils::getAvailableInVersions($nextFunction));
            }
        ));
    }
}
