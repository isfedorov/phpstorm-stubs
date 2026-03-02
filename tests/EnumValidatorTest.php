<?php

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProvider;
use StubTests\Framework\Validator\ValidatorTestBase;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumExistsCheck;
use StubTests\Sources\Validator\Enums\EnumFinalMethodsCheck;
use StubTests\Sources\Validator\Enums\EnumInterfacesCheck;
use StubTests\Sources\Validator\Enums\EnumMethodDeprecationCheck;
use StubTests\Sources\Validator\Enums\EnumMethodsExistCheck;
use StubTests\Sources\Validator\Enums\EnumMethodsOptionalParametersCheck;
use StubTests\Sources\Validator\Enums\EnumMethodsParameterTypesCheck;
use StubTests\Sources\Validator\Enums\EnumMethodsParametersCountCheck;
use StubTests\Sources\Validator\Enums\EnumMethodsReturnTypesCheck;
use StubTests\Sources\Validator\Enums\EnumMethodsVisibilityCheck;
use StubTests\Sources\Validator\Enums\EnumNamespaceCheck;
use StubTests\Sources\Validator\Enums\EnumStaticMethodsCheck;

/**
 * Validates that enums from reflection exist in stubs and their methods are correct.
 *
 * Enums were introduced in PHP 8.1, so all checks use PHP_8_1 as the lower bound.
 *
 * Each enum is tested individually as a separate test case,
 * making it easy to identify failures and re-run specific tests.
 */
class EnumValidatorTest extends ValidatorTestBase
{
    #[DataProvider('entityProvider')]
    public function testEntity(string $methodName, string $entityId, string $phpVersion): void
    {
        parent::testEntity($methodName, $entityId, $phpVersion);
    }

    /**
     * Override to provide enums as entities.
     */
    protected static function getEntitiesForMethod(string $methodName, $reflection): iterable
    {
        return $reflection->getEnums();
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkEnumExists(string $enumId, string $phpVersion): void
    {
        $this->executeCheck(
            new EnumExistsCheck(),
            $enumId,
            $phpVersion,
            "Enum {$enumId} exists in PHP {$phpVersion} but not in stubs"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkEnumNamespace(string $enumId, string $phpVersion): void
    {
        $this->executeCheck(
            new EnumNamespaceCheck(),
            $enumId,
            $phpVersion,
            "Enum {$enumId} namespace validation failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkEnumMethodsExist(string $enumId, string $phpVersion): void
    {
        $this->executeCheck(
            new EnumMethodsExistCheck(),
            $enumId,
            $phpVersion,
            "Enum {$enumId} methods check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkEnumStaticMethods(string $enumId, string $phpVersion): void
    {
        $this->executeCheck(
            new EnumStaticMethodsCheck(),
            $enumId,
            $phpVersion,
            "Enum {$enumId} static methods check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkEnumFinalMethods(string $enumId, string $phpVersion): void
    {
        $this->executeCheck(
            new EnumFinalMethodsCheck(),
            $enumId,
            $phpVersion,
            "Enum {$enumId} final methods check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkEnumMethodsVisibility(string $enumId, string $phpVersion): void
    {
        $this->executeCheck(
            new EnumMethodsVisibilityCheck(),
            $enumId,
            $phpVersion,
            "Enum {$enumId} methods visibility check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkEnumMethodsDeprecation(string $enumId, string $phpVersion): void
    {
        $this->executeCheck(
            new EnumMethodDeprecationCheck(),
            $enumId,
            $phpVersion,
            "Enum {$enumId} methods deprecation check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkEnumMethodsParametersCount(string $enumId, string $phpVersion): void
    {
        $this->executeCheck(
            new EnumMethodsParametersCountCheck(),
            $enumId,
            $phpVersion,
            "Enum {$enumId} methods parameters count check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkEnumMethodsReturnTypes(string $enumId, string $phpVersion): void
    {
        $this->executeCheck(
            new EnumMethodsReturnTypesCheck(),
            $enumId,
            $phpVersion,
            "Enum {$enumId} methods return type check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkEnumMethodsParameterTypes(string $enumId, string $phpVersion): void
    {
        $this->executeCheck(
            new EnumMethodsParameterTypesCheck(),
            $enumId,
            $phpVersion,
            "Enum {$enumId} methods parameter types check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkEnumMethodsOptionalParameters(string $enumId, string $phpVersion): void
    {
        $this->executeCheck(
            new EnumMethodsOptionalParametersCheck(),
            $enumId,
            $phpVersion,
            "Enum {$enumId} methods optional parameters check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkEnumInterfaces(string $enumId, string $phpVersion): void
    {
        $this->executeCheck(
            new EnumInterfacesCheck(),
            $enumId,
            $phpVersion,
            "Enum {$enumId} interfaces check failed in PHP {$phpVersion}"
        );
    }
}
