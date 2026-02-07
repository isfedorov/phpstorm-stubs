<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PrivateAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\ProtectedAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\PublicAccessModifier;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<ReflectionMethod>
 */
class ReflectionMethodParser implements Parser
{

    public function canParseReflectionClass($object)
    {
        // TODO: Implement canParseReflectionClass() method.
    }

    public function parse($object)
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