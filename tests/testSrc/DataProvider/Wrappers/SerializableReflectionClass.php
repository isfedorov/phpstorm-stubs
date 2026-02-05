<?php

namespace StubTests\Sources\DataProvider\Wrappers;

/**
 * Serializable wrapper around ReflectionClass
 *
 * This wrapper uses automatic extraction to get all data from a ReflectionClass
 * with custom handling for complex nested structures (methods, properties, etc.)
 *
 * PHP 5.6+ compatible (no typed properties, no return types)
 */
class SerializableReflectionClass extends AbstractSerializableReflection
{
    public function __construct($reflectionClass)
    {
        // Use generic extraction for basic properties
        $this->extractFromReflection($reflectionClass);

        // Custom handling for complex nested structures
        $this->postExtract($reflectionClass);
    }

    /**
     * Configure which methods to skip or customize
     */
    protected function getExtractionConfig()
    {
        $config = parent::getExtractionConfig();

        // Skip methods that return complex objects we'll handle manually
        $config['skipMethods'] = array(
            'getMethods',
            'getProperties',
            'getReflectionConstants',
            'getConstants',
            'getParentClass',
            'getInterfaces',
            'getMethod',
            'getProperty',
            'getConstructor',
            'getExtension',
            'getExtensionName',
            'getFileName',
            'getStartLine',
            'getEndLine',
            'getDocComment',
            'getStaticProperties',
            'getStaticPropertyValue',
            'getTraits',
            'getTraitAliases',
            'getTraitNames',
            'getInterfaceNames',
            'getAttributes'
        );

        return $config;
    }

    /**
     * Handle complex nested extraction after basic extraction
     */
    protected function postExtract($reflectionClass)
    {
        // Extract methods
        $methods = array();
        foreach ($reflectionClass->getMethods() as $method) {
            $methods[] = new SerializableReflectionMethod($method);
        }
        $this->setData('getMethods', $methods);

        // Extract properties
        $properties = array();
        foreach ($reflectionClass->getProperties() as $property) {
            $properties[] = new SerializableReflectionProperty($property);
        }
        $this->setData('getProperties', $properties);

        // Extract constants (modern or legacy way)
        $hasReflectionConstantsMethod = method_exists($reflectionClass, 'getReflectionConstants');

        if ($hasReflectionConstantsMethod) {
            $reflectionConstants = array();
            foreach ($reflectionClass->getReflectionConstants() as $constant) {
                $reflectionConstants[] = new SerializableReflectionClassConstant($constant);
            }
            $this->setData('getReflectionConstants', $reflectionConstants);
            $this->setData('getConstants', array());
        } else {
            $this->setData('getReflectionConstants', array());
            $this->setData('getConstants', $reflectionClass->getConstants());
        }

        // Extract parent class (avoid infinite recursion)
        $parentClass = $reflectionClass->getParentClass();
        $this->setData('getParentClass', $parentClass !== false ? new SerializableReflectionClass($parentClass) : false);

        // Extract interfaces
        $interfaces = array();
        foreach ($reflectionClass->getInterfaces() as $interface) {
            $interfaces[] = new SerializableReflectionClass($interface);
        }
        $this->setData('getInterfaces', $interfaces);
    }

    // Implement ReflectionClass interface methods explicitly for IDE support
    public function getName()
    {
        return $this->getData('getName');
    }

    public function getShortName()
    {
        return $this->getData('getShortName');
    }

    public function getNamespaceName()
    {
        return $this->getData('getNamespaceName');
    }

    public function isFinal()
    {
        return $this->getData('isFinal');
    }

    public function isReadOnly()
    {
        return $this->getData('isReadOnly', false);
    }

    public function isInternal()
    {
        return $this->getData('isInternal');
    }

    public function isInterface()
    {
        return $this->getData('isInterface');
    }

    public function isEnum()
    {
        return $this->getData('isEnum', false);
    }

    public function isAbstract()
    {
        return $this->getData('isAbstract');
    }

    public function getMethods()
    {
        return $this->getData('getMethods', array());
    }

    public function getProperties()
    {
        return $this->getData('getProperties', array());
    }

    public function hasMethod($name)
    {
        // Special handling for checking method existence
        if ($name === 'getReflectionConstants') {
            $constants = $this->getData('getReflectionConstants', array());
            return !empty($constants);
        }
        return parent::hasMethod($name);
    }

    public function getReflectionConstants()
    {
        return $this->getData('getReflectionConstants', array());
    }

    public function getConstants()
    {
        return $this->getData('getConstants', array());
    }

    public function getParentClass()
    {
        return $this->getData('getParentClass', false);
    }

    public function getInterfaces()
    {
        return $this->getData('getInterfaces', array());
    }
}
