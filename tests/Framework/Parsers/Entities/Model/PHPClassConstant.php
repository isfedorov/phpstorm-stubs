<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class PHPClassConstant extends BasePHPElement
{
    public $value;
    public $parentId;
    public $visibility = 'public'; // Default to public
    public bool $isFinal = false;

    public function getValue()
    {
        return $this->value;
    }

    public function isFinal(): bool
    {
        return $this->isFinal;
    }

    public function setValue(\PhpParser\Node\Expr $getValue)
    {
        // Handle different expression types
        if ($getValue instanceof \PhpParser\Node\Scalar\String_) {
            $this->value = $getValue->value;
        } elseif ($getValue instanceof \PhpParser\Node\Scalar\LNumber) {
            $this->value = $getValue->value;
        } elseif ($getValue instanceof \PhpParser\Node\Scalar\DNumber) {
            $this->value = $getValue->value;
        } elseif ($getValue instanceof \PhpParser\Node\Expr\UnaryMinus) {
            // Handle negative numbers: -123 becomes UnaryMinus with expr property
            if ($getValue->expr instanceof \PhpParser\Node\Scalar\LNumber) {
                $this->value = -$getValue->expr->value;
            } elseif ($getValue->expr instanceof \PhpParser\Node\Scalar\DNumber) {
                $this->value = -$getValue->expr->value;
            } else {
                $this->value = null;
            }
        } elseif ($getValue instanceof \PhpParser\Node\Expr\UnaryPlus) {
            // Handle positive numbers: +123 becomes UnaryPlus with expr property
            if ($getValue->expr instanceof \PhpParser\Node\Scalar\LNumber) {
                $this->value = $getValue->expr->value;
            } elseif ($getValue->expr instanceof \PhpParser\Node\Scalar\DNumber) {
                $this->value = $getValue->expr->value;
            } else {
                $this->value = null;
            }
        } else {
            // For complex expressions (arrays, operations, etc.), store null
            $this->value = null;
        }
    }
}