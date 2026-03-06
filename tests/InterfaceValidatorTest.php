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
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsParameterDefaultValueCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsParameterNamesCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsTentativeReturnTypeCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodDeprecationCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceParentInterfacesCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceConstantsCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceConstantsValueCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceConstantsVisibilityCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsNullableTypeForbiddenCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsReturnTypeForbiddenCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsUnionTypeForbiddenCheck;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsPhpDocConformsSignatureCheck;

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

    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkInterfaceConstantsVisibility(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceConstantsVisibilityCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} constants visibility check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkInterfaceConstantsValue(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceConstantsValueCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} constants value check failed in PHP {$phpVersion}"
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

    #[PhpVersionRange(PhpVersions::LATEST, PhpVersions::LATEST)]
    public function checkInterfaceMethodsParameterDefaultValue(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodsParameterDefaultValueCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} methods parameter default value check failed in PHP {$phpVersion}"
        );
    }

    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkInterfaceMethodsPhpDocConformsSignature(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodsPhpDocConformsSignatureCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} PhpDoc/signature type mismatch in PHP {$phpVersion}"
        );
    }

    /**
     * Check that interface methods available before PHP 7.0 do not declare any
     * return type hints, since return type hints were introduced in PHP 7.0.
     */
    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::EARLIEST)]
    public function checkMethodDoesNotHaveReturnTypeHint(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodsReturnTypeForbiddenCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} has method with return type hint available before PHP 7.0 in PHP {$phpVersion}"
        );
    }

    /**
     * Check that interface methods available before PHP 7.1 do not declare nullable
     * type hints, since implementations targeting PHP 5.6/7.0 cannot use ?T syntax.
     */
    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_0)]
    public function checkMethodDoesNotHaveNullableTypeHint(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodsNullableTypeForbiddenCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} has method with nullable type hint available before PHP 7.1 in PHP {$phpVersion}"
        );
    }

    /**
     * Check that interface methods available before PHP 8.0 do not declare union
     * type hints, since implementations targeting PHP 5.6–7.4 cannot use T1|T2 syntax.
     */
    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4)]
    public function checkMethodDoesNotHaveUnionTypeHint(string $interfaceId, string $phpVersion): void
    {
        $this->executeCheck(
            new InterfaceMethodsUnionTypeForbiddenCheck(),
            $interfaceId,
            $phpVersion,
            "Interface {$interfaceId} has method with union type hint available before PHP 8.0 in PHP {$phpVersion}"
        );
    }
}
