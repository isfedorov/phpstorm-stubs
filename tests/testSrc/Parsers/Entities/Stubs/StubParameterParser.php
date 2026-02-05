<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Model\Entities\PHPParameter;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ParameterNode;

/**
 * Parses ParameterNode AST nodes into PHPParameter domain objects.
 * Currently extracts parameter name only, as PHPParameter model is minimal.
 */
class StubParameterParser
{
    /**
     * Parses a parameter AST node into PHPParameter domain object.
     *
     * @param ParameterNode $node The parameter AST node
     * @return PHPParameter
     */
    public function parseNode(ParameterNode $node): PHPParameter
    {
        return new PHPParameter($node->getName());
    }
}
