<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionClassConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionClassConstant;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<AdaptedReflectionClassConstant|array>
 */
class ReflectionClassConstantParser implements Parser
{

    public function canParse($object): bool
    {
        return false;
    }

    /**
     * Parse a ReflectionClassConstant (adapted or array) into a PHPClassConstant model
     *
     * Accepts both AdaptedReflectionClassConstant objects and arrays for backward compatibility.
     *
     * @param \StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionClassConstant|array $object
     * @return PHPClassConstant
     */
    public function parse($object): PHPClassConstant
    {
        // Accept both AdaptedReflectionClassConstant and array (duck typing)
        if (is_object($object) && method_exists($object, 'getName') && method_exists($object, 'getValue')) {
            $constant = new PhpClassConstant();
            if (!empty($object->getName())) {
                $constant->setName($object->getName());
            }
            $constant->value = $object->getValue();
            if ($object->getDeclaringClass()->getName()) {
                $constant->parentId = '\\' . $object->getDeclaringClass()->getName();
            }
            if ($object->isPrivate()) {
                $constant->visibility = 'private';
            } elseif ($object->isProtected()) {
                $constant->visibility = 'protected';
            } else {
                $constant->visibility = 'public';
            }
        } else {
            foreach (array_keys($object) as $constantName) {
                $constant = new PHPClassConstant();
                $constant->setName($constantName);
                $constant->value = $object[$constant->getName()];
            }
        }
        return $constant;
    }
}