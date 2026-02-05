<?php

namespace StubTests\Sources\Parsers;
/**
 * @template T
 */
interface Parser
{
    /**
     * @param T $object
     */
    public function canParseReflectionClass($object);

    /**
     * @param T $object
     */
    public function parse($object);
}