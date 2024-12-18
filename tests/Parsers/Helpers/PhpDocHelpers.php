<?php

namespace StubTests\Parsers\Helpers;

use phpDocumentor\Reflection\DocBlock;

class PhpDocHelpers
{
    /**
     * @return bool
     */
    public static function hasInheritDocLikeTag(DocBlock $phpDoc)
    {
        return $phpDoc->hasTag('inheritdoc') || $phpDoc->hasTag('inheritDoc') ||
            stripos($phpDoc->getSummary(), 'inheritdoc') > 0;
    }
}