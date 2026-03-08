<?php

namespace StubTests\Framework\Parsers\Entities\Model\Access;

class PublicAccessModifier implements AccessModifier
{
    public function toString(): string
    {
        return 'public';
    }
}