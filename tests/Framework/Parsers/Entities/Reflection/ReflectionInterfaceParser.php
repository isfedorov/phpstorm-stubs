<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionClass;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<AdaptedReflectionClass>
 */
class ReflectionInterfaceParser implements Parser
{

    public function canParseReflectionClass($object): bool
    {
        return $object->isInternal() && $object->isInterface();
    }

    /**
     * Parse an AdaptedReflectionClass (representing an interface) into a PHPInterface model
     *
     * @param AdaptedReflectionClass $object
     * @return PHPInterface
     */
    public function parse($object): PHPInterface
    {
        $interface = new PHPInterface();
        $interface->setName($object->getShortName());
        $interface->setNamespace($object->getNamespaceName() ? '\\' . $object->getNamespaceName() : '\\');
        $interface->setId($interface->getNamespace() != '\\' ? $interface->getNamespace() . '\\' . $interface->getName() : '\\' . $interface->getName());
        foreach ($object->getMethods() as $method) {
            $interface->methods []= new ReflectionMethodParser()->parse($method);
        }
        if ($object->hasMethod('getReflectionConstants')) {
            foreach ($object->getReflectionConstants() as $reflectionConstant) {
                $interface->constants []= new ReflectionClassConstantParser()->parse($reflectionConstant);
            }
        } else {
            foreach ($object->getConstants() as $constantName => $constantValue) {
                $interface->constants []= new ReflectionClassConstantParser()->parse([$constantName => $constantValue]);
            }
        }
        return $interface;
    }
}