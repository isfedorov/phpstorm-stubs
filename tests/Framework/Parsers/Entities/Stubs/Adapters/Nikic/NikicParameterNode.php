<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic;

use PhpParser\Node\Param;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\AttributeNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ParameterNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\TypeNode;

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

    public function getType(): ?TypeNode
    {
        if ($this->param->type === null) {
            return null;
        }

        return new NikicTypeNode($this->param->type);
    }

    public function getAttributes(): array
    {
        $attributes = [];
        foreach ($this->param->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attributes[] = new NikicAttributeNode($attr);
            }
        }
        return $attributes;
    }

    public function isVariadic(): bool
    {
        return $this->param->variadic;
    }
}
