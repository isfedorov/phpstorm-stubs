<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic;

use PhpParser\Node\Param;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ParameterNode;

/**
 * Adapter for nikic/php-parser Param nodes.
 */
class NikicParameterNode implements ParameterNode
{
    private Param $param;

    public function __construct(Param $param)
    {
        $this->param = $param;
    }

    public function getName(): string
    {
        return $this->param->var->name;
    }
}
