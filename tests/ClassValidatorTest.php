<?php

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProvider;
use StubTests\Framework\Validator\ValidatorTestBase;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Validator\ClassExistsCheck;
use StubTests\Sources\Validator\ClassNamespaceCheck;
use StubTests\Sources\Validator\ClassParentClassCheck;
use StubTests\Sources\Validator\ClassReadonlyCheck;
use StubTests\Sources\Validator\ClassFinalCheck;

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
    #[PhpVersionRange('5.6', '8.4')]
    public function checkClassExists(string $classId, string $phpVersion): void
    {
        $this->executeCheck(
            new ClassExistsCheck(),
            $classId,
            $phpVersion,
            "Class {$classId} exists in PHP {$phpVersion} but not in stubs"
        );
    }

	#[PhpVersionRange('5.6','8.4')]
	public function checkClassNamespace(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassNamespaceCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} namespace validation failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange('8.2','8.4')]
	public function checkClassReadonly(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassReadonlyCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} readonly validation failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange('5.6','8.4')]
	public function checkClassFinal(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassFinalCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} final validation failed in PHP {$phpVersion}"
		);
	}

	#[PhpVersionRange('5.6','8.4')]
	public function checkParentClass(string $classId, string $phpVersion): void
	{
		$this->executeCheck(
			new ClassParentClassCheck(),
			$classId,
			$phpVersion,
			"Class {$classId} parent class validation failed in PHP {$phpVersion}"
		);
	}
}
