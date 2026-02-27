<?php

namespace StubTests\Sources\Runner;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class PhpVersionRange
{
    public string $from;
    public string $to;

    public function __construct(PhpVersions|string $from, PhpVersions|string|null $to = null)
    {
        $this->from = $from instanceof PhpVersions ? $from->value : $from;
        $to ??= $from;
        $this->to = $to instanceof PhpVersions ? $to->value : $to;
    }

    public function includes(string $version): bool
    {
        return version_compare($version, $this->from, '>=') && version_compare($version, $this->to, '<=');
    }
}
