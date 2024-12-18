<?php

namespace StubTests\Model;

use PhpParser\Node;

interface PHPDocumentable
{
    function collectTags(Node $node);
}