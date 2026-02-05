<?php

namespace StubTests\Sources\DataProvider\Wrappers;

/**
 * Serializable wrapper around ReflectionMethod
 *
 * Uses automatic extraction with custom handling for declaring class
 *
 * PHP 5.6+ compatible
 */
class SerializableReflectionMethod extends AbstractSerializableReflection
{
    public function __construct($reflectionMethod)
    {
        // Use generic extraction for basic properties
        $this->extractFromReflection($reflectionMethod);

        // Custom handling for special cases
        $this->postExtract($reflectionMethod);
    }

    /**
     * Configure which methods to skip
     */
    protected function getExtractionConfig()
    {
        $config = parent::getExtractionConfig();

        // Skip methods that return complex objects or require special handling
        $config['skipMethods'] = array(
            'getDeclaringClass',
            'getParameters',
            'getReturnType',
            'getClosure',
            'invoke',
            'invokeArgs',
            'getPrototype',
            'getExtension',
            'getExtensionName',
            'getFileName',
            'getStartLine',
            'getEndLine',
            'getDocComment',
            'getStaticVariables',
            'getAttributes'
        );

        return $config;
    }

    /**
     * Handle complex properties after basic extraction
     */
    protected function postExtract($reflectionMethod)
    {
        // Store declaring class as a minimal reference to avoid recursion
        $declaringClass = $reflectionMethod->getDeclaringClass();
        $this->setData('declaringClassName', $declaringClass->getName());
    }

    // Implement ReflectionMethod interface methods explicitly for IDE support
    public function getName()
    {
        return $this->getData('getName');
    }

    public function getDeclaringClass()
    {
        // Return a minimal wrapper with just the name
        return new SerializableReflectionClassReference($this->getData('declaringClassName'));
    }

    public function isPublic()
    {
        return $this->getData('isPublic');
    }

    public function isProtected()
    {
        return $this->getData('isProtected');
    }

    public function isPrivate()
    {
        return $this->getData('isPrivate');
    }

    public function isStatic()
    {
        return $this->getData('isStatic');
    }

    public function isFinal()
    {
        return $this->getData('isFinal');
    }

    public function isAbstract()
    {
        return $this->getData('isAbstract');
    }

    public function isDeprecated()
    {
        return $this->getData('isDeprecated');
    }
}
