<?php

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProvider;
use StubTests\Framework\Validator\ValidatorTestBase;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\FunctionDeprecationCheck;
use StubTests\Sources\Validator\FunctionExistsCheck;
use StubTests\Sources\Validator\FunctionParametersCountCheck;
use StubTests\Sources\Validator\ParameterNamesCheck;
use StubTests\Sources\Validator\ParameterTypesCheck;
use StubTests\Sources\Validator\FunctionReturnTypesCheck;

/**
 * Validates that functions from reflection match stubs.
 *
 * Each function is tested individually as a separate test case,
 * making it easy to identify failures and re-run specific tests.
 */
class FunctionValidatorTest extends ValidatorTestBase
{
    /**
     * Data provider that yields all functions from reflection for each supported PHP version.
     *
     * @return iterable<string, array{string, string, string}>
     */
    #[DataProvider('entityProvider')]
    public function testEntity(string $methodName, string $entityId, string $phpVersion): void
    {
        parent::testEntity($methodName, $entityId, $phpVersion);
    }

    /**
     * Override to provide functions as entities.
     */
    protected static function getEntitiesForMethod(string $methodName, $reflection): iterable
    {
        // Return all functions from reflection
        return $reflection->getFunctions();
    }

    /**
     * Check that each function from reflection exists in stubs.
     *
     * This check runs on all PHP versions from 5.6 to 8.4.
     */
    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkFunctionExists(string $functionId, string $phpVersion): void
    {
        $this->executeCheck(
            new FunctionExistsCheck(),
            $functionId,
            $phpVersion,
            "Function {$functionId} exists in PHP {$phpVersion} but not in stubs"
        );
    }

    /**
     * Check that parameter names match between reflection and stubs.
     *
     * This check only runs on PHP 8.0+ where named parameters were introduced.
     */
    #[PhpVersionRange(PhpVersions::PHP_8_0, PhpVersions::LATEST)]
    public function checkParameterNames(string $functionId, string $phpVersion): void
    {
        $this->executeCheck(
            new ParameterNamesCheck(),
            $functionId,
            $phpVersion,
            "Function {$functionId} has mismatched parameter names in PHP {$phpVersion}"
        );
    }

    /**
     * Check that parameter types match between reflection and stubs.
     *
     * This check runs on PHP 7.0+ where type hints became more comprehensive.
     */
    #[PhpVersionRange(PhpVersions::PHP_7_0, PhpVersions::LATEST)]
    public function checkParameterTypes(string $functionId, string $phpVersion): void
    {
        $this->executeCheck(
            new ParameterTypesCheck(),
            $functionId,
            $phpVersion,
            "Function {$functionId} has mismatched parameter types in PHP {$phpVersion}"
        );
    }

    /**
     * Check that return types match between reflection and stubs.
     *
     * This check runs on PHP 7.0+ where return type declarations were introduced.
     */
    #[PhpVersionRange(PhpVersions::PHP_7_0, PhpVersions::LATEST)]
    public function checkReturnTypes(string $functionId, string $phpVersion): void
    {
        $this->executeCheck(
            new FunctionReturnTypesCheck(),
            $functionId,
            $phpVersion,
            "Function {$functionId} has mismatched return type in PHP {$phpVersion}"
        );
    }

    /**
     * Check that functions deprecated in reflection are also deprecated in stubs.
     */
    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkFunctionsDeprecation(string $functionId, string $phpVersion): void
    {
        $this->executeCheck(
            new FunctionDeprecationCheck(),
            $functionId,
            $phpVersion,
            "Function {$functionId} deprecation mismatch in PHP {$phpVersion}"
        );
    }

    /**
     * Check that the number of parameters matches between reflection and stubs.
     */
    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkFunctionParametersCount(string $functionId, string $phpVersion): void
    {
        $this->executeCheck(
            new FunctionParametersCountCheck(),
            $functionId,
            $phpVersion,
            "Function {$functionId} has parameter count mismatch in PHP {$phpVersion}"
        );
    }
}
