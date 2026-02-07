<?php

namespace StubTests\Sources\Parsers\Entities\Reflection\Wrappers;

/**
 * Minimal reference to a class (just the name)
 * Used to avoid infinite recursion when serializing class relationships
 *
 * PHP 5.6+ compatible
 */
class AdaptedReflectionClassReference
{
    private $name;

    public function __construct($className)
    {
        $this->name = $className;
    }

    public function getName()
    {
        return $this->name;
    }
}
