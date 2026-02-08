<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PrivateAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\ProtectedAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\PublicAccessModifier;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionMethod;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<AdaptedReflectionMethod>
 */
class ReflectionMethodParser implements Parser
{

    public function canParse($object): bool
    {
        return false;
    }

    /**
     * Parse an AdaptedReflectionMethod into a PHPMethod model
     *
     * @param AdaptedReflectionMethod $object
     * @return PHPMethod
     */
    public function parse($object): PHPMethod
    {
        $method = new PHPMethod();
        $method->setName($object->getName());
        $method->setId('\\' . $object->getDeclaringClass()->getName() . '::' . $object->getName());
        if ($object->isProtected()) {
            $method->setAccess(new ProtectedAccessModifier());
        } elseif ($object->isPrivate()) {
            $method->setAccess(new PrivateAccessModifier());
        } else {
            $method->setAccess(new PublicAccessModifier());
        }
        $method->setIsStatic($object->isStatic());
        $method->setIsFinal($object->isFinal());
        $method->setIsAbstract($object->isAbstract());
        $method->setIsDeprecated($object->isDeprecated());
        return $method;
    }
}