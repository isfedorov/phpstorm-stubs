<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPConstant;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<ReflectionConstant>
 */
class ReflectionModernConstantParser implements Parser
{

    public function canParseReflectionClass($object): bool
    {
        return false;
    }

    public function parse($object): PHPConstant
    {
        $namespace = '\\';
        $parsedConstant = new PHPConstant();

        // Handle both ReflectionConstant objects and arrays (duck typing)
        if (is_object($object) && method_exists($object, 'getShortName')) {
            $parsedConstant->setName($object->getShortName());
            $parsedConstant->value = $object->getValue();
            if ($object->getNamespaceName()) {
                $namespace .= $object->getNamespaceName() . '\\';
            }
        } elseif (is_array($object)) {
            // Fallback: handle indexed array format [name, value]
            $parsedConstant->setName($object[0]);
            $parsedConstant->value = is_resource($object[1]) ? 'PHPSTORM_RESOURCE' : $object[1];
        }

        $parsedConstant->setId($namespace . $parsedConstant->getName());
        return $parsedConstant;
    }
}