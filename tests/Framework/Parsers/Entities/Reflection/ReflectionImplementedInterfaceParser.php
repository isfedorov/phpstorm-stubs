<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<\ReflectionClass>
 */
class ReflectionImplementedInterfaceParser implements Parser
{

    public function canParseReflectionClass($object)
    {
        // TODO: Implement canParseReflectionClass() method.
    }

    public function parse($object)
    {
        $parsedInterface = new PHPInterface();
        $fqn = explode('\\', $object->getName());
        if (empty($object->getShortName())) {
            $parsedInterface->setName(array_pop($fqn));
        } else {
            $parsedInterface->setName($object->getShortName());
        }
        $parsedInterface->setNamespace($object->getNamespaceName());
        $parsedInterface->setId($parsedInterface->getNamespace() . '\\' . $parsedInterface->getName());
        return $parsedInterface;
    }
}