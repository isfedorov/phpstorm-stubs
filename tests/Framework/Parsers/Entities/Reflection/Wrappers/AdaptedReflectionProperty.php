<?php

namespace StubTests\Sources\Parsers\Entities\Reflection\Wrappers;

/**
 * Adapter wrapper around ReflectionProperty
 *
 * Uses automatic extraction to get all property data
 *
 * PHP 5.6+ compatible
 */
class AdaptedReflectionProperty extends AbstractReflectionAdapter
{
    public function __construct($reflectionProperty)
    {
        // Use generic extraction for all properties
        $this->extractFromReflection($reflectionProperty);

        // Custom handling if needed
        $this->postExtract($reflectionProperty);
    }

    /**
     * Configure which methods to skip
     */
    protected function getExtractionConfig()
    {
        $config = parent::getExtractionConfig();

        // Skip methods that return complex objects or require parameters
        $config['skipMethods'] = array(
            'getDeclaringClass',
            'getValue',
            'setValue',
            'getType',
            'getDefaultValue',
            'getDocComment',
            'getAttributes'
        );

        return $config;
    }

    /**
     * Handle complex properties after basic extraction
     */
    protected function postExtract($reflectionObject)
    {
        // Store declaring class name if needed to avoid recursion
        $declaringClass = $reflectionObject->getDeclaringClass();
        $this->setData('declaringClassName', $declaringClass->getName());

        // Handle type if present (PHP 7.4+)
        if (method_exists($reflectionObject, 'hasType') && $reflectionObject->hasType()) {
            $type = $reflectionObject->getType();
            if ($type) {
                $this->setData('getType', new AdaptedReflectionType($type));
            }
        }
    }

    // Implement ReflectionProperty interface methods explicitly for IDE support
    public function getName()
    {
        return $this->getData('getName');
    }

    public function isPublic()
    {
        return $this->getData('isPublic', false);
    }

    public function isProtected()
    {
        return $this->getData('isProtected', false);
    }

    public function isPrivate()
    {
        return $this->getData('isPrivate', false);
    }

    public function isStatic()
    {
        return $this->getData('isStatic', false);
    }

    public function isDefault()
    {
        return $this->getData('isDefault', false);
    }

    public function getDeclaringClass()
    {
        // Return a minimal wrapper with just the name
        return new AdaptedReflectionClassReference($this->getData('declaringClassName'));
    }
}
