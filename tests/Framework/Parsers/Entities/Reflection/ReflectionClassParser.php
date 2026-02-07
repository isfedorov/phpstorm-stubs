<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<ReflectionClass>
 */
class ReflectionClassParser implements Parser
{

    public function canParseReflectionClass($object)
    {
        return $object->isInternal() && !$object->isInterface() && !$object->isEnum();
    }

    public function parse($object)
    {
        $class = new PhpClass();
        $class->setName(!empty($object->getShortName()) ? $object->getShortName() : null);
        $class->setNamespace($object->getNamespaceName() ? '\\' . $object->getNamespaceName() : '\\');
        if ($class->getName()) {
            $class->setId($class->getNamespace() != '\\' ? $class->getNamespace() . '\\' . $class->getName() : '\\' . $class->getName());
        }
        $class->isFinal = $object->isFinal();
        $class->isReadonly = $object->isReadOnly();
        foreach ($object->getMethods() as $method) {
            $class->methods []= new ReflectionMethodParser()->parse($method);
        }
        foreach ($object->getProperties() as $property) {
            $class->properties []= new ReflectionPropertyParser()->parse($property);
        }
        if ($object->hasMethod('getReflectionConstants')) {
            foreach ($object->getReflectionConstants() as $reflectionConstant) {
                $class->constants []= new ReflectionClassConstantParser()->parse($reflectionConstant);
            }
        } else {
            foreach ($object->getConstants() as $constantName => $constantValue) {
                $class->constants []= new ReflectionClassConstantParser()->parse([$constantName => $constantValue]);
            }
        }
        if ($object->getParentClass()) {
            $class->parentClass = new ReflectionParentClassParser()->parse($object->getParentClass());
        }
        if ($object->getInterfaces()) {
            foreach ($object->getInterfaces() as $interface) {
                $class->interfaces []= new ReflectionImplementedInterfaceParser()->parse($interface);
            }
        }
        return $class;
    }
}