<?php

namespace StubTests\Framework\Parsers\Entities\Model\Access;

class PrivateAccessModifier implements AccessModifier
{
    public function toString(): string
    {
        return 'private';
    }
}