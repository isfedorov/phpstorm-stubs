<?php

namespace StubTests\Sources\Parsers\Entities\Reflection\Wrappers;

/**
 * Serializable wrapper around ReflectionClassConstant (PHP 7.1+)
 *
 * Uses automatic extraction with custom handling for declaring class
 *
 * PHP 5.6+ compatible
 */
class SerializableReflectionClassConstant extends AbstractSerializableReflection
{
    public function __construct($reflectionConstant)
    {
        // Use generic extraction for basic properties
        $this->extractFromReflection($reflectionConstant);

        // Custom handling for special cases
        $this->postExtract($reflectionConstant);
    }

    /**
     * Configure which methods to skip
     */
    protected function getExtractionConfig()
    {
        $config = parent::getExtractionConfig();

        // Skip methods that return complex objects
        $config['skipMethods'] = array(
            'getDeclaringClass',
            'getDocComment',
            'getAttributes'
        );

        return $config;
    }

    /**
     * Handle complex properties after basic extraction
     */
    protected function postExtract($reflectionConstant)
    {
        // Store declaring class as a minimal reference to avoid recursion
        $declaringClass = $reflectionConstant->getDeclaringClass();
        $this->setData('declaringClassName', $declaringClass->getName());
    }

    // Implement ReflectionClassConstant interface methods explicitly for IDE support
    public function getName()
    {
        return $this->getData('getName');
    }

    public function getValue()
    {
        return $this->getData('getValue');
    }

    public function getDeclaringClass()
    {
        // Return a minimal reference to avoid recursion
        return new SerializableReflectionClassReference($this->getData('declaringClassName'));
    }

    public function isPrivate()
    {
        return $this->getData('isPrivate', false);
    }

    public function isProtected()
    {
        return $this->getData('isProtected', false);
    }

    public function isPublic()
    {
        return $this->getData('isPublic', true);
    }

    public function isFinal()
    {
        return $this->getData('isFinal', false);
    }

    public function isEnumCase()
    {
        return $this->getData('isEnumCase', false);
    }
}
