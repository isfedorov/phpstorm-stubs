<?php

namespace StubTests\Model;

use RuntimeException;
use StubTests\Model\Predicats\ConstantsFilterPredicateProvider;
use StubTests\Model\Predicats\FunctionsFilterPredicateProvider;
use function array_key_exists;
use function count;

abstract class BasePHPClass extends PHPNamespacedElement
{
    /** @var PHPMethod[] */
    public $methods = [];

    /** @var PHPClassConstant[] */
    public $constants = [];

    /** @var bool */
    public $isFinal = false;

    public function addConstant(PHPClassConstant $parsedConstant)
    {
        if (!isset($parsedConstant->name)) {
            throw new RuntimeException("Constant name is not set");
        }
        if ($this->constantExists($parsedConstant)) {
            $this->addDuplicateConstant($parsedConstant);
        } else {
            $this->constants[$parsedConstant->name] = $parsedConstant;
        }
    }

    public function getConstant($constantName, $filterCallback = null)
    {
        if ($filterCallback === null) {
            $filterCallback = ConstantsFilterPredicateProvider::getDefaultSuitableClassConstants($constantName);
        }
        $constants = array_filter($this->constants, $filterCallback);
        return array_pop($constants);
    }

    public function addMethod(PHPMethod $parsedMethod)
    {
        if (!isset($parsedMethod->name)) {
            throw new RuntimeException("Constant name is not set");
        }
        if ($this->methodExists($parsedMethod)) {
            $this->addDuplicateMethod($parsedMethod);
        } else {
            $this->methods[$parsedMethod->name] = $parsedMethod;
        }
    }

    public function getMethod($searchCriteria, $filterCallback = null)
    {
        if ($filterCallback === null) {
            $filterCallback = FunctionsFilterPredicateProvider::getDefaultSuitableMethods($searchCriteria);
        }
        $methods = array_filter($this->methods, $filterCallback);
        return array_pop($methods);
    }

    /**
     * @return bool
     */
    private function constantExists(PHPClassConstant $parsedConstant)
    {
        return array_key_exists($parsedConstant->name, $this->constants);
    }

    private function addDuplicateConstant(PHPClassConstant $parsedConstant)
    {
        $duplicateCount = $this->getDuplicateCount($parsedConstant->name);
        $duplicateConstantName = $parsedConstant->name . '_duplicated_' . $duplicateCount;
        $this->constants[$duplicateConstantName] = $parsedConstant;
    }

    /**
     * @param string $constantName
     * @return int
     */
    private function getDuplicateCount($constantName)
    {
        return count(array_filter($this->constants, function (PHPClassConstant $existingConstant) use ($constantName) {
            return $existingConstant->name === $constantName;
        }));
    }

    /**
     * @return bool
     */
    private function methodExists(PHPMethod $method)
    {
        return array_key_exists($method->name, $this->methods);
    }

    private function addDuplicateMethod(PHPMethod $method)
    {
        $duplicateCount = $this->countDuplicateMethods($method->name);
        $duplicateName = $method->name . "_duplicated_" . $duplicateCount;

        $this->methods[$duplicateName] = $method;
    }

    /**
     * @param string $methodName
     * @return int
     */
    private function countDuplicateMethods($methodName)
    {
        return count(array_filter($this->methods, function (PHPMethod $existing) use ($methodName) {
            return $existing->name === $methodName;
        }));
    }

}
