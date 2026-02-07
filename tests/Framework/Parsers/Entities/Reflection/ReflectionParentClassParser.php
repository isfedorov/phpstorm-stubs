<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<ReflectionClass>
 */
class ReflectionParentClassParser implements Parser
{
    public function canParseReflectionClass($object)
    {
        // TODO: Implement canParseReflectionClass() method.
    }

    public function parse($object)
    {
        $class = new PHPClass();
        $class->setName($object->getName());
        return $class;
    }
}