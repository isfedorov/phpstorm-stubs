<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use StubTests\Sources\Model\Entities\PHPInterface;
use StubTests\Sources\Model\Entities\PHPMethod;

class ReflectionInterfaceParser
{

    public function canParseReflectionClass($reflectionClass)
    {
        return $reflectionClass->isInternal() && $reflectionClass->isInterface();
    }

    public function parse($object)
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