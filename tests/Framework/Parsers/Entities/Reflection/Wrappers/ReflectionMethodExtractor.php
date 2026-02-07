<?php

namespace StubTests\Sources\Parsers\Entities\Reflection\Wrappers;

/**
 * Automatic method extraction logic for Reflection objects
 *
 * This class automatically discovers and calls getter methods on Reflection objects,
 * extracting all available data dynamically. This ensures forward compatibility when
 * new methods are added to PHP's Reflection API.
 *
 * PHP 5.6+ compatible
 */
class ReflectionMethodExtractor
{
    /**
     * Extract data from a reflection object by automatically calling all getter methods
     *
     * @param object $reflectionObject The reflection object to extract data from
     * @param array $config Configuration for extraction behavior
     * @return array Extracted data as associative array
     */
    public static function extractData($reflectionObject, array $config = array())
    {
        $data = array();
        $reflectionClass = new \ReflectionClass($reflectionObject);

        // Default configuration
        $defaultConfig = array(
            'methodPrefixes' => array('is', 'has', 'get'),
            'includeNameMethod' => true,
            'skipMethods' => array(),
            'customHandlers' => array(),
            'maxDepth' => 3
        );

        $config = array_merge($defaultConfig, $config);

        // Get all public methods
        $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $methodName = $method->getName();

            // Skip if method is in skip list
            if (in_array($methodName, $config['skipMethods'])) {
                continue;
            }

            // Skip methods that require parameters
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            // Skip magic methods and internal methods
            if (strpos($methodName, '__') === 0) {
                continue;
            }

            // Check if method matches expected prefixes or is getName()
            $shouldExtract = false;
            foreach ($config['methodPrefixes'] as $prefix) {
                if (strpos($methodName, $prefix) === 0) {
                    $shouldExtract = true;
                    break;
                }
            }

            if (!$shouldExtract) {
                continue;
            }

            // Check if method exists on the object (for version compatibility)
            if (!method_exists($reflectionObject, $methodName)) {
                continue;
            }

            // Check for custom handler
            if (isset($config['customHandlers'][$methodName])) {
                $handler = $config['customHandlers'][$methodName];
                $data[$methodName] = $handler($reflectionObject, $methodName);
                continue;
            }

            // Extract the value
            try {
                $value = $reflectionObject->$methodName();

                // Store the raw value or mark for later processing
                $data[$methodName] = $value;

            } catch (\Exception $e) {
                // If method call fails, skip it (don't store)
                continue;
            } catch (\Throwable $e) {
                // Catch all errors including TypeError for PHP 7+
                continue;
            }
        }

        return $data;
    }

    /**
     * Convert extracted data to serializable format
     * Handles Reflection objects, arrays, and primitives
     *
     * @param mixed $value The value to convert
     * @param int $depth Current recursion depth
     * @param int $maxDepth Maximum recursion depth
     * @return mixed Serializable value
     */
    public static function makeSerializable($value, $depth = 0, $maxDepth = 3)
    {
        // Prevent infinite recursion
        if ($depth >= $maxDepth) {
            return null;
        }

        // Handle null
        if ($value === null || $value === false) {
            return $value;
        }

        // Handle arrays
        if (is_array($value)) {
            $result = array();
            foreach ($value as $key => $item) {
                $result[$key] = self::makeSerializable($item, $depth + 1, $maxDepth);
            }
            return $result;
        }

        // Handle Reflection objects - wrap them
        if (is_object($value)) {
            // Check if it's a Reflection object
            $className = get_class($value);

            // Map Reflection classes to their adapter wrappers
            if ($className === 'ReflectionClass') {
                return new AdaptedReflectionClass($value);
            }
            if ($className === 'ReflectionMethod') {
                return new AdaptedReflectionMethod($value);
            }
            if ($className === 'ReflectionProperty') {
                return new AdaptedReflectionProperty($value);
            }
            if ($className === 'ReflectionParameter') {
                return new AdaptedReflectionParameter($value);
            }
            if ($className === 'ReflectionFunction') {
                return new AdaptedReflectionFunction($value);
            }
            if ($className === 'ReflectionClassConstant') {
                return new AdaptedReflectionClassConstant($value);
            }
            if ($className === 'ReflectionNamedType') {
                return new AdaptedReflectionType($value);
            }
            if ($className === 'ReflectionUnionType' || $className === 'ReflectionIntersectionType') {
                return new AdaptedReflectionType($value);
            }
            // Handle base ReflectionType (catch-all for any type objects)
            if ($value instanceof \ReflectionType) {
                return new AdaptedReflectionType($value);
            }

            // For already adapted wrappers, return as-is
            if (strpos($className, 'AdaptedReflection') === 0 || strpos($className, 'StubTests\\Sources\\Parsers\\Entities\\Reflection\\Wrappers\\AdaptedReflection') === 0) {
                return $value;
            }

            // For other objects, try to convert to string or return class name
            if (method_exists($value, '__toString')) {
                return (string)$value;
            }

            return $className;
        }

        // Return primitives as-is
        return $value;
    }

    /**
     * Get a property key name from a method name
     * Converts method name like "isAbstract" to "isAbstract" for storage
     *
     * @param string $methodName
     * @return string
     */
    public static function getPropertyKey($methodName)
    {
        return $methodName;
    }
}
