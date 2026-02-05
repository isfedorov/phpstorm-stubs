<?php

namespace StubTests\Sources\Runner;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class PhpVersionRange
{
    public function __construct(public string $from, public ?string $to = null)
    {
        $this->to ??= $from;
    }

    public function includes(string $version): bool
    {
        return version_compare($version, $this->from, '>=') && version_compare($version, $this->to, '<=');
    }
}