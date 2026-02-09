<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionParameter;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<AdaptedReflectionParameter>
 */
class ReflectionParameterParser implements Parser
{
    private ReflectionTypeParser $typeParser;

    public function __construct(?ReflectionTypeParser $typeParser = null)
    {
        $this->typeParser = $typeParser ?? new ReflectionTypeParser();
    }

    public function canParse($object): bool
    {
        return $object instanceof AdaptedReflectionParameter;
    }

    /**
     * Parse an AdaptedReflectionParameter into a PHPParameter model
     *
     * @param AdaptedReflectionParameter $object
     * @return PHPParameter
     */
    public function parse($object): PHPParameter
    {
        $parameter = new PHPParameter($object->getName());

        // Set position
        $parameter->setPosition($object->getPosition());

        // Set optional
        $parameter->setIsOptional($object->isOptional());

        // Set variadic
        $parameter->setIsVariadic($object->isVariadic());

        // Set passed by reference
        $parameter->setIsPassedByReference($object->isPassedByReference());

        // Parse type information using ReflectionTypeParser
        $type = $this->typeParser->parse($object->hasType() ? $object->getType() : null);
        $parameter->setType($type);

        // Parse default value
        if ($object->isDefaultValueAvailable()) {
            $parameter->setHasDefaultValue(true);
            try {
                $parameter->setDefaultValue($object->getDefaultValue());
            } catch (\Exception $e) {
                // If we can't get the default value, leave it as null
                $parameter->setDefaultValue(null);
            }
        } else {
            $parameter->setHasDefaultValue(false);
        }

        return $parameter;
    }
}
