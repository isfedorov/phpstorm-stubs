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

    public function canParse(mixed $object): bool
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
        // Use the full name (e.g. 'Random\Engine') so that namespaced interface names
        // are stored consistently with how stubs write them in the `implements` clause.
        $parsedInterface->setName($object->getName());
        $parsedInterface->setNamespace($object->getNamespaceName());
        $parsedInterface->setId('\\' . $object->getName());
        return $parsedInterface;
    }
}