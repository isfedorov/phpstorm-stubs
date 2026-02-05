<?php

namespace StubTests\Sources\DataProvider\Wrappers;

/**
 * Serializable wrapper for a named type (simple helper)
 *
 * PHP 5.6+ compatible
 */
class SerializableReflectionNamedType
{
    private $name;

    public function __construct($typeName)
    {
        $this->name = $typeName;
    }

    public function getName()
    {
        return $this->name;
    }
}
