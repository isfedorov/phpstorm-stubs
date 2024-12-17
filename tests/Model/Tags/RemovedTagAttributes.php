<?php

namespace StubTests\Model\Tags;

use phpDocumentor\Reflection\DocBlock\Description;

class RemovedTagAttributes
{
    public readonly string $name;
    public function __construct(public ?string $version = null, public ?Description $description = null)
    {
        $this->name = 'removed';
    }
}