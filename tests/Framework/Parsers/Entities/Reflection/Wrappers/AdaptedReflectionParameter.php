<?php

namespace StubTests\Sources\Parsers\Entities\Reflection\Wrappers;

/**
 * Adapter wrapper around ReflectionParameter
 *
 * Uses automatic extraction to get all parameter data
 *
 * PHP 5.6+ compatible
 */
class AdaptedReflectionParameter extends AbstractReflectionAdapter
{
    public function __construct($reflectionParameter)
    {
        // Use generic extraction for all properties
        $this->extractFromReflection($reflectionParameter);

        // Custom handling if needed
        $this->postExtract($reflectionParameter);
    }

    /**
     * Configure which methods to skip
     */
    protected function getExtractionConfig()
    {
        $config = parent::getExtractionConfig();

        // Skip methods that return complex objects or have side effects
        $config['skipMethods'] = array(
            'getClass',
            'getDeclaringClass',
            'getDeclaringFunction',
            'getType',
            'getDefaultValue',
            'getDefaultValueConstantName',
            'getAttributes'
        );

        return $config;
    }

    // Implement ReflectionParameter interface methods explicitly for IDE support
    public function getName()
    {
        return $this->getData('getName');
    }

    public function isOptional()
    {
        return $this->getData('isOptional', false);
    }

    public function isDefaultValueAvailable()
    {
        return $this->getData('isDefaultValueAvailable', false);
    }

    public function isVariadic()
    {
        return $this->getData('isVariadic', false);
    }

    public function isPassedByReference()
    {
        return $this->getData('isPassedByReference', false);
    }

    public function canBePassedByValue()
    {
        return $this->getData('canBePassedByValue', true);
    }

    public function allowsNull()
    {
        return $this->getData('allowsNull', true);
    }

    public function getPosition()
    {
        return $this->getData('getPosition', 0);
    }
}
