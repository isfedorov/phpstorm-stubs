<?php

namespace StubTests\Framework\Parsers\Entities\Model\Access;

class ProtectedAccessModifier implements AccessModifier
{
    public function toString(): string
    {
        return 'protected';
    }
}