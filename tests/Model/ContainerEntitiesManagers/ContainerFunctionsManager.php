<?php

namespace StubTests\Model\ContainerEntitiesManagers;

use RuntimeException;
use StubTests\Model\PHPFunction;
use StubTests\Model\Predicats\FunctionsFilterPredicateProvider;
use StubTests\Parsers\ParserUtils;

class ContainerFunctionsManager
{
    /**
     * @var PHPFunction[]
     */
    private $functions = [];

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