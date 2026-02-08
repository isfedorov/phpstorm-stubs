<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionProperty;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionProperty;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<AdaptedReflectionProperty>
 */
class ReflectionPropertyParser implements Parser
{
    public function canParseReflectionClass($object): bool
    {
        return false;
    }

    /**
     * Parse an AdaptedReflectionProperty into a PHPProperty model
     *
     * @param AdaptedReflectionProperty $object
     * @return PHPProperty
     */
    public function parse($object): PHPProperty
    {
        $property = new PHPProperty();
        $property->setName($object->getName());
        return $property;
    }
}