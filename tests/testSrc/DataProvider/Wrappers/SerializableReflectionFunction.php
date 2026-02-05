<?php

namespace StubTests\Sources\DataProvider\Wrappers;

/**
 * Serializable wrapper around ReflectionFunction
 *
 * Uses automatic extraction with custom handling for parameters and return type
 *
 * PHP 5.6+ compatible
 */
class SerializableReflectionFunction extends AbstractSerializableReflection
{
    public function __construct($reflectionFunction)
    {
        // Use generic extraction for basic properties
        $this->extractFromReflection($reflectionFunction);

        // Custom handling for complex nested structures
        $this->postExtract($reflectionFunction);
    }

    /**
     * Configure which methods to skip
     */
    protected function getExtractionConfig()
    {
        $config = parent::getExtractionConfig();

        // Skip methods that return complex objects we'll handle manually
        $config['skipMethods'] = array(
            'getParameters',
            'getReturnType',
            'getClosure',
            'invoke',
            'invokeArgs',
            'getExtension',
            'getExtensionName',
            'getFileName',
            'getStartLine',
            'getEndLine',
            'getDocComment',
            'getStaticVariables',
            'getAttributes',
            'getClosureThis',
            'getClosureScopeClass',
            'getClosureCalledClass'
        );

        return $config;
    }

    /**
     * Handle complex nested extraction after basic extraction
     */
    protected function postExtract($reflectionFunction)
    {
        // Extract return type if exists (PHP 7.0+)
        if (method_exists($reflectionFunction, 'hasReturnType') && $reflectionFunction->hasReturnType()) {
            $returnType = $reflectionFunction->getReturnType();
            if ($returnType) {
                $this->setData('getReturnType', new SerializableReflectionType($returnType));
            }
        } else {
            $this->setData('getReturnType', null);
        }

        // Extract parameters
        $parameters = array();
        foreach ($reflectionFunction->getParameters() as $parameter) {
            $parameters[] = new SerializableReflectionParameter($parameter);
        }
        $this->setData('getParameters', $parameters);
    }

    // Implement ReflectionFunction interface methods explicitly for IDE support
    public function getName()
    {
        return $this->getData('getName');
    }

    public function getNamespaceName()
    {
        return $this->getData('getNamespaceName');
    }

    public function isDeprecated()
    {
        return $this->getData('isDeprecated');
    }

    public function hasReturnType()
    {
        return $this->getData('hasReturnType', false);
    }

    public function getReturnType()
    {
        return $this->getData('getReturnType');
    }

    public function getParameters()
    {
        return $this->getData('getParameters', array());
    }

    public function getNumberOfParameters()
    {
        return $this->getData('getNumberOfParameters', 0);
    }

    public function getNumberOfRequiredParameters()
    {
        return $this->getData('getNumberOfRequiredParameters', 0);
    }

    public function isInternal()
    {
        return $this->getData('isInternal', false);
    }

    public function isUserDefined()
    {
        return $this->getData('isUserDefined', false);
    }

    public function isGenerator()
    {
        return $this->getData('isGenerator', false);
    }

    public function isVariadic()
    {
        return $this->getData('isVariadic', false);
    }
}
