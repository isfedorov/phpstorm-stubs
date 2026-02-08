<?php

namespace StubTests\Sources\Parsers\Entities\Reflection\Wrappers;

/**
 * Adapter wrapper around ReflectionType (ReflectionNamedType, ReflectionUnionType, etc.)
 *
 * Uses automatic extraction with custom handling for union/intersection types
 *
 * PHP 5.6+ compatible
 */
class AdaptedReflectionType extends AbstractReflectionAdapter
{
    public function __construct($reflectionType)
    {
        // Use generic extraction for basic properties
        $this->extractFromReflection($reflectionType);

        // Custom handling for complex type structures
        $this->postExtract($reflectionType);
    }

    /**
     * Get additional skip methods specific to ReflectionType
     * Most common patterns are now in ReflectionTypeRegistry::getGlobalSkipPatterns()
     */
    protected function getAdditionalSkipMethods()
    {
        // Skip methods that need special handling based on type variant
        return array(
            'getName',      // Handle specially based on union/intersection/named
            '__toString'    // Should not be auto-extracted
        );
    }

    /**
     * Handle complex type structures after basic extraction
     */
    protected function postExtract($reflectionObject)
    {
        // Detect type kind
        $isUnion = $reflectionObject instanceof \ReflectionUnionType;
        $isIntersection = class_exists('\ReflectionIntersectionType') && $reflectionObject instanceof \ReflectionIntersectionType;
        $isNamed = $reflectionObject instanceof \ReflectionNamedType;

        $this->setData('isUnionType', $isUnion);
        $this->setData('isIntersectionType', $isIntersection);

        // Handle union types (PHP 8.0+)
        if ($isUnion) {
            $types = array();
            foreach ($reflectionObject->getTypes() as $type) {
                if ($type instanceof \ReflectionNamedType) {
                    $types[] = $type->getName();
                }
            }
            $this->setData('getTypes', $types);
            $this->setData('getName', null);
        }
        // Handle intersection types (PHP 8.1+)
        elseif ($isIntersection) {
            $types = array();
            foreach ($reflectionObject->getTypes() as $type) {
                $types[] = $type->getName();
            }
            $this->setData('getTypes', $types);
            $this->setData('getName', null);
        }
        // Handle named types (PHP 7.0+)
        elseif ($isNamed) {
            $this->setData('getName', $reflectionObject->getName());
            $this->setData('getTypes', array());
        }
        // Fallback
        else {
            $this->setData('getName', null);
            $this->setData('getTypes', array());
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
        $types = $this->getData('getTypes', array());
        $result = array();
        foreach ($types as $typeName) {
            $result[] = new AdaptedReflectionNamedType($typeName);
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
