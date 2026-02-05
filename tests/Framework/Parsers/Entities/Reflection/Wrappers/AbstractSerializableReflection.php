<?php

namespace StubTests\Sources\Parsers\Entities\Reflection\Wrappers;

/**
 * Abstract base class for serializable Reflection wrappers
 *
 * Provides automatic extraction of data from Reflection objects using introspection.
 * Subclasses can override configuration or add custom extraction logic.
 *
 * PHP 5.6+ compatible (no typed properties, no return types)
 */
abstract class AbstractSerializableReflection
{
    /**
     * Extracted data from the reflection object
     * @var array
     */
    protected $data;

    /**
     * Extract configuration - override in subclasses if needed
     *
     * @return array Configuration for ReflectionMethodExtractor
     */
    protected function getExtractionConfig()
    {
        return array(
            'methodPrefixes' => array('is', 'has', 'get'),
            'includeNameMethod' => true,
            'skipMethods' => array(),
            'customHandlers' => array()
        );
    }

    /**
     * Perform generic extraction from reflection object
     *
     * @param object $reflectionObject
     */
    protected function extractFromReflection($reflectionObject)
    {
        $config = $this->getExtractionConfig();
        $rawData = ReflectionMethodExtractor::extractData($reflectionObject, $config);

        // Convert to serializable format
        $this->data = array();
        foreach ($rawData as $methodName => $value) {
            $key = ReflectionMethodExtractor::getPropertyKey($methodName);
            $this->data[$key] = ReflectionMethodExtractor::makeSerializable($value);
        }
    }

    /**
     * Post-extraction hook for custom processing
     * Override in subclasses if needed
     *
     * @param object $reflectionObject Original reflection object
     */
    protected function postExtract($reflectionObject)
    {
        // Override in subclasses if custom processing is needed
    }

    /**
     * Magic method to proxy method calls to stored data
     *
     * @param string $name Method name
     * @param array $arguments Method arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // Check if we have this method's data stored
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        // Throw error for unknown methods
        throw new \BadMethodCallException("Method {$name} does not exist or was not extracted");
    }

    /**
     * Check if a method exists in the extracted data
     *
     * @param string $methodName
     * @return bool
     */
    public function hasMethod($methodName)
    {
        return array_key_exists($methodName, $this->data);
    }

    /**
     * Get all extracted data (for debugging)
     *
     * @return array
     */
    public function getExtractedData()
    {
        return $this->data;
    }

    /**
     * Get a specific value from extracted data
     *
     * @param string $key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    protected function getData($key, $default = null)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    /**
     * Set a value in extracted data
     *
     * @param string $key
     * @param mixed $value
     */
    protected function setData($key, $value)
    {
        $this->data[$key] = $value;
    }
}
