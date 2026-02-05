<?php

namespace StubTests\Sources\DataProvider;

/**
 * Data provider that works with pre-extracted (wrapped) reflection data
 *
 * This provider returns serializable wrappers instead of class/function names,
 * allowing the reflection parsers to work with pre-extracted data from a different PHP runtime.
 */
class PreExtractedReflectionDataProvider implements ReflectionDataProvider
{
    private array $data;

    public function __construct(array $extractedData)
    {
        $this->data = $extractedData;
    }

    /**
     * Returns wrapped ReflectionClass objects (not names)
     * Each item is a SerializableReflectionClass that can be passed to parsers
     */
    public function getReflectionClasses(): array
    {
        return $this->data['classes'] ?? [];
    }

    /**
     * Returns wrapped ReflectionClass objects for interfaces
     */
    public function getReflectionInterfaces(): array
    {
        return $this->data['interfaces'] ?? [];
    }

    /**
     * Returns wrapped ReflectionClass objects for enums
     */
    public function getReflectionEnums(): array
    {
        return $this->data['enums'] ?? [];
    }

    /**
     * Returns wrapped ReflectionFunction objects (not names)
     */
    public function getReflectionFunctions(): array
    {
        return $this->data['functions'] ?? [];
    }

    /**
     * Returns constants as simple array (name => value)
     */
    public function getReflectionConstants(): array
    {
        return $this->data['constants'] ?? [];
    }
}
