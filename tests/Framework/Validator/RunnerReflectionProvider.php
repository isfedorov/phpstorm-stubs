<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Runner\Runner;

/**
 * Default implementation of ReflectionProviderInterface that uses Runner.
 *
 * This wraps the static Runner::getReflection() call to allow dependency injection
 * in validator checks while maintaining the existing production behavior.
 */
class RunnerReflectionProvider implements ReflectionProviderInterface
{
    /**
     * Get reflection data for a specific PHP version using Runner.
     *
     * @param string $phpVersion The PHP version (e.g., '8.0', '8.1')
     * @return ParsedDataStorageManager The reflection data storage
     */
    public function getReflection(string $phpVersion): ParsedDataStorageManager
    {
        return Runner::getReflection($phpVersion);
    }
}
