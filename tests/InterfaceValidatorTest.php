<?php

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProvider;
use StubTests\Framework\Validator\ValidatorTestBase;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Interfaces\InterfaceExistsCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceNamespaceCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsExistCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceStaticMethodsCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsParametersCountCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsReturnTypesCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsOptionalParametersCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsParameterTypesCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsParameterNamesCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsTentativeReturnTypeCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodDeprecationCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceParentInterfacesCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceConstantsCheck;

/**
 * Validates that interfaces from reflection exist in stubs and their methods are correct.
 *
 * Each interface is tested individually as a separate test case,
 * making it easy to identify failures and re-run specific tests.
 */
class InterfaceValidatorTest extends ValidatorTestBase
{
    #[DataProvider('entityProvider')]
    public function testEntity(string $methodName, string $entityId, string $phpVersion): void
    {
        parent::testEntity($methodName, $entityId, $phpVersion);
    }

    /**
     * Override to provide interfaces as entities.
     */
    protected static function getEntitiesForMethod(string $methodName, $reflection): iterable
    {
        return $reflection->getInterfaces();
    }

    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkInterfaceExists(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceExistsCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} exists in PHP {$phpVersion} but not in stubs"
        );
    }

    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkInterfaceNamespace(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceNamespaceCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} namespace validation failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkInterfaceMethodsExist(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodsExistCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} methods check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkInterfaceStaticMethods(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceStaticMethodsCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} static methods check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkInterfaceMethodsParametersCount(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodsParametersCountCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} methods parameters count check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_7_0, PhpVersions::LATEST)]
    public function checkInterfaceMethodsReturnTypes(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodsReturnTypesCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} methods return type check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkInterfaceMethodsDeprecation(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodDeprecationCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} methods deprecation check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_7_0, PhpVersions::LATEST)]
    public function checkInterfaceMethodsParameterTypes(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodsParameterTypesCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} methods parameter types check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkInterfaceMethodsOptionalParameters(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodsOptionalParametersCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} methods optional parameters check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkInterfaceParent(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceParentInterfacesCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} parent interfaces check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkInterfaceConstants(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceConstantsCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} constants check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_0, PhpVersions::LATEST)]
    public function checkInterfaceMethodsParameterNames(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodsParameterNamesCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} methods parameter names check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST)]
    public function checkInterfaceMethodsTentativeReturnType(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodsTentativeReturnTypeCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} methods tentative return type check failed in PHP {$phpVersion}"
        );
    }
}
