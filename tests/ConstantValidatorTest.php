<?php

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProvider;
use StubTests\Framework\Validator\ValidatorTestBase;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Constants\ConstantExistsCheck;
use StubTests\Sources\Validator\Constants\ConstantValueCheck;

/**
 * Validates that global constants from reflection exist in stubs.
 *
 * Each constant is tested individually as a separate test case,
 * making it easy to identify failures and re-run specific tests.
 */
class ConstantValidatorTest extends ValidatorTestBase
{
    /**
     * Data provider that yields all global constants from reflection for each supported PHP version.
     *
     * @return iterable<string, array{string, string, string}>
     */
    #[DataProvider('entityProvider')]
    public function testEntity(string $methodName, string $entityId, string $phpVersion): void
    {
        parent::testEntity($methodName, $entityId, $phpVersion);
    }

    /**
     * Override to provide global constants as entities.
     */
    protected static function getEntitiesForMethod(string $methodName, $reflection): iterable
    {
        return $reflection->getConstants();
    }

    /**
     * Check that each global constant from reflection exists in stubs.
     *
     * This check runs on all PHP versions from 5.6 to 8.4.
     */
    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkConstantExists(string $constantId, string $phpVersion): void
    {
        $this->executeCheck(
            new ConstantExistsCheck(),
            $constantId,
            $phpVersion,
            "Constant {$constantId} exists in PHP {$phpVersion} but not in stubs"
        );
    }

    /**
     * Check that global constants in stubs have the same value as in reflection.
     *
     * Value comparison is limited to the latest PHP version since constant values
     * may differ across releases and stubs only track the current value.
     */
    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkConstantValue(string $constantId, string $phpVersion): void
    {
        $this->executeCheck(
            new ConstantValueCheck(),
            $constantId,
            $phpVersion,
            "Constant {$constantId} value mismatch in PHP {$phpVersion}"
        );
    }
}
