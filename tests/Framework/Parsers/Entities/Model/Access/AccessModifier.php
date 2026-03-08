<?php

namespace StubTests\Framework\Parsers\Entities\Model\Access;

/**
 * Common interface for access modifier value objects (public, protected, private).
 *
 * Used to represent visibility modifiers for class methods and properties in both
 * stub and reflection parsing contexts.
 */
interface AccessModifier
{
    /**
     * Returns the string representation of the access modifier.
     *
     * @return string One of: 'public', 'protected', 'private'
     */
    public function toString(): string;
}
