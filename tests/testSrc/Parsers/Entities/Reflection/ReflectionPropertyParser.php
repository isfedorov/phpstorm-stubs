<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionProperty;
use StubTests\Sources\Model\Entities\PHPProperty;
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
        $property->name = $object->getName();
        return $property;
    }
}