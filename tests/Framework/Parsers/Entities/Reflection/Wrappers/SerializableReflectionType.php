<?php

namespace StubTests\Sources\Parsers\Entities\Reflection\Wrappers;

/**
 * Serializable wrapper around ReflectionType (ReflectionNamedType, ReflectionUnionType, etc.)
 *
 * Uses automatic extraction with custom handling for union/intersection types
 *
 * PHP 5.6+ compatible
 */
class SerializableReflectionType extends AbstractSerializableReflection
{
    public function __construct($reflectionType)
    {
        // Use generic extraction for basic properties
        $this->extractFromReflection($reflectionType);

        // Custom handling for complex type structures
        $this->postExtract($reflectionType);
    }

    /**
     * Configure which methods to skip
     */
    protected function getExtractionConfig()
    {
        $config = parent::getExtractionConfig();

        // Skip methods that return complex objects or need special handling
        $config['skipMethods'] = array(
            'getTypes',
            'getName',  // We'll handle this specially based on type
            '__toString'
        );

        return $config;
    }

    /**
     * Handle complex type structures after basic extraction
     */
    protected function postExtract($reflectionType)
    {
        // Detect type kind
        $isUnion = $reflectionType instanceof \ReflectionUnionType;
        $isIntersection = class_exists('\ReflectionIntersectionType') && $reflectionType instanceof \ReflectionIntersectionType;
        $isNamed = $reflectionType instanceof \ReflectionNamedType;

        $this->setData('isUnionType', $isUnion);
        $this->setData('isIntersectionType', $isIntersection);

        // Handle union types (PHP 8.0+)
        if ($isUnion) {
            $types = array();
            foreach ($reflectionType->getTypes() as $type) {
                if ($type instanceof \ReflectionNamedType) {
                    $types[] = $type->getName();
                }
            }
            $this->setData('types', $types);
            $this->setData('getName', null);
        }
        // Handle intersection types (PHP 8.1+)
        elseif ($isIntersection) {
            $types = array();
            foreach ($reflectionType->getTypes() as $type) {
                $types[] = $type->getName();
            }
            $this->setData('types', $types);
            $this->setData('getName', null);
        }
        // Handle named types (PHP 7.0+)
        elseif ($isNamed) {
            $this->setData('getName', $reflectionType->getName());
            $this->setData('types', array());
        }
        // Fallback
        else {
            $this->setData('getName', null);
            $this->setData('types', array());
        }
    }

    // Implement ReflectionType interface methods explicitly for IDE support
    public function allowsNull()
    {
        return $this->getData('allowsNull', false);
    }

    public function getName()
    {
        return $this->getData('getName');
    }

    public function getTypes()
    {
        // Return array of pseudo-ReflectionNamedType objects
        $types = $this->getData('types', array());
        $result = array();
        foreach ($types as $typeName) {
            $result[] = new SerializableReflectionNamedType($typeName);
        }
        return $result;
    }

    public function isUnionType()
    {
        return $this->getData('isUnionType', false);
    }

    public function isIntersectionType()
    {
        return $this->getData('isIntersectionType', false);
    }

    public function isBuiltin()
    {
        return $this->getData('isBuiltin', false);
    }
}
