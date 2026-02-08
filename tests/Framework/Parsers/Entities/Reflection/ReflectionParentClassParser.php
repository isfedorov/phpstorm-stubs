<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionClassReference;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<AdaptedReflectionClassReference>
 */
class ReflectionParentClassParser implements Parser
{
    public function canParse($object): bool
    {
        return false;
    }

    /**
     * Parse an AdaptedReflectionClassReference (parent class reference) into a PHPClass model
     *
     * @param AdaptedReflectionClassReference $object
     * @return PHPClass
     */
    public function parse($object): PHPClass
    {
        $class = new PHPClass();
        $class->setName($object->getName());
        return $class;
    }
}