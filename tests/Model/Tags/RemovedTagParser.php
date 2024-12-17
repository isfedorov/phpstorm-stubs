<?php

namespace StubTests\Model\Tags;

class RemovedTagParser
{
    const string TAG_MATCHER = '((?:\d\S*|[^\s\:]+\:\s*\$[^\$]+\$))\s*(.+)?';
    private(set) ?string $version;
    private(set) string $description;

    public function tagDoesNotContainVersion(): bool
    {
        return $this->version === null;
    }

    public function parse(string $body): void
    {
        $matches = [];
        preg_match('/^' . self::TAG_MATCHER . '$/sux', $body, $matches);
        $this->version = $matches[1] ?? null;
        $this->description = $matches[2] ?? '';
    }
}