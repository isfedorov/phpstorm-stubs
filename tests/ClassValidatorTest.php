<?php

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProvider;
use StubTests\Framework\Validator\ValidatorTestBase;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Classes\ClassExistsCheck;
use StubTests\Sources\Validator\Classes\ClassInterfacesCheck;
use StubTests\Sources\Validator\Classes\ClassNamespaceCheck;
use StubTests\Sources\Validator\Classes\ClassParentClassCheck;
use StubTests\Sources\Validator\Classes\ClassReadonlyCheck;
use StubTests\Sources\Validator\Classes\ClassFinalCheck;
use StubTests\Sources\Validator\Classes\ClassFinalMethodsCheck;
use StubTests\Sources\Validator\Classes\ClassMethodsExistCheck;
use StubTests\Sources\Validator\Classes\ClassMethodsVisibilityCheck;
use StubTests\Sources\Validator\Classes\ClassStaticPropertiesCheck;
use StubTests\Sources\Validator\Classes\ClassPropertiesExistCheck;
use StubTests\Sources\Validator\Classes\ClassPropertiesVisibilityCheck;
use StubTests\Sources\Validator\Classes\ClassPropertiesTypeCheck;
use StubTests\Sources\Validator\Classes\ClassMethodsParametersCountCheck;
use StubTests\Sources\Validator\Classes\ClassMethodsReturnTypesCheck;
use StubTests\Sources\Validator\Classes\ClassMethodsOptionalParametersCheck;
use StubTests\Sources\Validator\Classes\ClassMethodsParameterTypesCheck;
use StubTests\Sources\Validator\Classes\MethodDeprecationCheck;
use StubTests\Sources\Validator\Classes\ClassStaticMethodsCheck;
use StubTests\Sources\Validator\Classes\ClassConstantsCheck;

/**
 * Validates that classes from reflection exist in stubs.
 *
 * Each class is tested individually as a separate test case,
 * making it easy to identify failures and re-run specific tests.
 */
class ClassValidatorTest extends ValidatorTestBase
{
    /**
     * Data provider that yields all classes from reflection for each supported PHP version.
     *
     * @return iterable<string, array{string, string, string}>
     */
    #[DataProvider('entityProvider')]
    public function testEntity(string $methodName, string $entityId, string $phpVersion): void
    {
        parent::testEntity($methodName, $entityId, $phpVersion);
    }

    /**
     * Override to provide classes as entities.
     */
    protected static function getEntitiesForMethod(string $methodName, $reflection): iterable
    {
        // Return all classes from reflection
        return $reflection->getClasses();
    }

    /**
     * Check that each class from reflection exists in stubs.
     *
     * This check runs on all PHP versions from 5.6 to 8.4.
     */
    #[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
    public function checkClassExists(string $classId, string $phpVersion): void
    {
        $this->executeCheck(
            new ClassExistsCheck(),
            $classId,
            $phpVersion,
            "Class {$classId} exists in PHP {$phpVersion} but not in stubs"
        );
    }

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassNamespace(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassNamespaceCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} namespace validation failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::PHP_8_2, PhpVersions::LATEST)]
	public function checkClassReadonly(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassReadonlyCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} readonly validation failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassFinal(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassFinalCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} final validation failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkParentClass(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassParentClassCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} parent class validation failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassInterfaces(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassInterfacesCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} interfaces validation failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassesMethodsExist(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassMethodsExistCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} methods check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassesFinalMethods(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassFinalMethodsCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} final methods check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassesStaticMethods(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassStaticMethodsCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} static methods check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassProperties(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassPropertiesExistCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} properties check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassesMethodsVisibility(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassMethodsVisibilityCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} methods visibility check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassStaticProperties(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassStaticPropertiesCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} static properties check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassPropertiesVisibility(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassPropertiesVisibilityCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} properties visibility check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::PHP_7_4, PhpVersions::LATEST)]
	public function checkClassPropertiesType(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassPropertiesTypeCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} properties type check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassMethodsParametersCount(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassMethodsParametersCountCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} methods parameters count check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::PHP_7_0, PhpVersions::LATEST)]
	public function checkClassMethodsReturnTypes(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassMethodsReturnTypesCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} methods return type check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkMethodsDeprecation(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new MethodDeprecationCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} methods deprecation check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::PHP_7_0, PhpVersions::LATEST)]
	public function checkClassMethodsParameterTypes(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassMethodsParameterTypesCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} methods parameter types check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassMethodsOptionalParameters(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassMethodsOptionalParametersCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} methods optional parameters check failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST)]
	public function checkClassConstants(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassConstantsCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} constants check failed in PHP {$phpVersion}"
		);
	}
}
