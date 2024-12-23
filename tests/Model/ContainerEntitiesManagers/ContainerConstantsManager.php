<?php

namespace StubTests\Model\ContainerEntitiesManagers;

use RuntimeException;
use StubTests\Model\PHPConstant;
use StubTests\Model\PHPDefineConstant;
use StubTests\Model\Predicats\ConstantsFilterPredicateProvider;

class ContainerConstantsManager
{
    /**
     * @var PHPConstant[]
     */
    private $constants = [];

    /**
     * @return PHPConstant[]
     */
    public function getConstants()
    {
        return $this->constants;
    }

    public function getConstant($filterCallback)
    {
        $constants = array_filter($this->constants, $filterCallback);
        if (count($constants) > 1) {
            throw new RuntimeException("Multiple constants found");
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
}