<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<ReflectionEnum>
 */
class ReflectionEnumParser implements Parser
{

    public function canParseReflectionClass($object)
    {
        return $object->isInternal() && $object->isEnum();
    }

    public function parse($object)
    {
        $parsedEnum = new PHPEnum();
        $parsedEnum->setName($object->getShortName());
        $parsedEnum->setNamespace($object->getNamespaceName() ? '\\' . $object->getNamespaceName() : '\\');
        if ($parsedEnum->getNamespace() !== '\\') {
            $parsedEnum->setId($parsedEnum->getNamespace() . '\\' . $parsedEnum->getName());
        } else {
            $parsedEnum->setId('\\' . $parsedEnum->getName());
        }
        $parsedEnum->isFinal = $object->isFinal();
        $parsedEnum->isReadonly = $object->isReadOnly();
        foreach ($object->getMethods() as $method) {
            $parsedEnum->methods []= new ReflectionMethodParser()->parse($method);
        }
        if ($object->hasMethod('getReflectionConstants')) {
            foreach ($object->getReflectionConstants() as $reflectionConstant) {
                $parsedEnum->constants []= new ReflectionClassConstantParser()->parse($reflectionConstant);
            }
        } else {
            foreach ($object->getConstants() as $key => $value) {
                $parsedEnum->constants []= new ReflectionClassConstantParser()->parse([$key => $value]);
            }
        }
        foreach ($object->getInterfaces() as $interface) {
            $parsedEnum->interfaces []= new ReflectionImplementedInterfaceParser()->parse($interface);
        }
        return $parsedEnum;
    }
}