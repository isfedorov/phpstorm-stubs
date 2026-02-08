<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionClassReference;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<AdaptedReflectionClassReference>
 */
class ReflectionImplementedInterfaceParser implements Parser
{

    public function canParseReflectionClass(mixed $object): bool
    {
        return false;
    }

    /**
     * Parse an AdaptedReflectionClassReference (interface reference) into a PHPInterface model
     *
     * @param AdaptedReflectionClassReference $object
     * @return PHPInterface
     */
    public function parse($object): PHPInterface
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