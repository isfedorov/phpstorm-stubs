<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionProperty;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<ReflectionProperty>
 */
class ReflectionPropertyParser implements Parser
{
    public function canParseReflectionClass($object)
    {
        // TODO: Implement canParseReflectionClass() method.
    }

    public function parse($object)
    {
        $property = new PHPProperty();
        $property->setName($object->getName());
        return $property;
    }
}