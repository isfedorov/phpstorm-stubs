<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use StubTests\Sources\Parsers\Entities\Model\PHPConstant;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<array>
 */
class ReflectionDefineConstantParser implements Parser
{

    public function canParseReflectionClass($object)
    {
        // TODO: Implement canParseReflectionClass() method.
    }

    public function parse($object)
    {
        $parsedConstant = new PHPConstant();
        $parsedConstant->setName($object[0]);
        if (is_resource($object[1])) {
            $parsedConstant->value = 'PHPSTORM_RESOURCE';
        } else {
            $parsedConstant->value = $object[1];
        }
        $parsedConstant->setId('\\' . $parsedConstant->getName());
        return $parsedConstant;
    }

}