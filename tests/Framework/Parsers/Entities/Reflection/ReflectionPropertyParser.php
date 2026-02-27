<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use StubTests\Framework\Parsers\Entities\Model\Access\PrivateAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\ProtectedAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\PublicAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionProperty;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<AdaptedReflectionProperty>
 */
class ReflectionPropertyParser implements Parser
{

    private ReflectionTypeParser $typeParser;

    public function __construct(?ReflectionTypeParser $typeParser = null)
    {
        $this->typeParser = $typeParser ?? new ReflectionTypeParser();
    }

    public function canParse($object): bool
    {
        return false;
    }

    /**
     * Parse an AdaptedReflectionProperty into a PHPProperty model
     *
     * @param AdaptedReflectionProperty $object
     * @return PHPProperty
     */
    public function parse($object): PHPProperty
    {
        $property = new PHPProperty();
        $property->setName($object->getName());
        $property->setIsStatic($object->isStatic());
        if ($object->isProtected()) {
            $property->setAccess(new ProtectedAccessModifier());
        } elseif ($object->isPrivate()) {
            $property->setAccess(new PrivateAccessModifier());
        } else {
            $property->setAccess(new PublicAccessModifier());
        }
        $property->setTypeFromSignature($this->typeParser->parse($object->getType() ?? null));

        // Parse default value if available
        if (method_exists($object, 'hasDefaultValue') && $object->hasDefaultValue()) {
            $property->setDefaultValue($object->getDefaultValue());
        }

        return $property;
    }
}