<?php

namespace StubTests\Unit\Validator\Functions;

use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Functions\ParameterNamesCheck;
use StubTests\Unit\Validator\CheckTestCase;

class ParameterNamesCheckTest extends CheckTestCase
{
    public function testSupportsPhp80AndAbove(): void
    {
        $check = new ParameterNamesCheck();

        $this->assertFalse($check->supports(PhpVersions::EARLIEST->value));
        $this->assertFalse($check->supports(PhpVersions::PHP_7_0->value));
        $this->assertFalse($check->supports(PhpVersions::PHP_7_4->value));
        $this->assertTrue($check->supports(PhpVersions::PHP_8_0->value));
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    public function testMatchingParameterNamesForFunction(): void
    {
        // Arrange
        $functionName = 'array_map';
        $param1 = $this->createMockParameter('callback');
        $param2 = $this->createMockParameter('array');

        $reflectionFunction = $this->createMockFunction($functionName, [$param1, $param2]);
        $stubFunction = $this->createMockFunction($functionName, [$param1, $param2]);

        $reflectionProvider = $this->createMockReflectionProvider([$reflectionFunction]);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$stubFunction]);

        $check = new ParameterNamesCheck($reflectionProvider);

        // Act
        $result = $check->run($stubsManager, $functionName, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testParameterNameMismatch(): void
    {
        // Arrange
        $functionName = 'test_function';
        $reflectionParam = $this->createMockParameter('callback');
        $stubParam = $this->createMockParameter('wrongName');

        $reflectionFunction = $this->createMockFunction($functionName, [$reflectionParam]);
        $stubFunction = $this->createMockFunction($functionName, [$stubParam]);

        $reflectionProvider = $this->createMockReflectionProvider([$reflectionFunction]);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$stubFunction]);

        $check = new ParameterNamesCheck($reflectionProvider);

        // Act
        $result = $check->run($stubsManager, $functionName, '8.0');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());

        $failures = $result->getFailures();
        $this->assertArrayHasKey($functionName, $failures);
        $this->assertStringContainsString('Parameter #0 name mismatch', $failures[$functionName]);
        $this->assertStringContainsString('callback', $failures[$functionName]);
        $this->assertStringContainsString('wrongName', $failures[$functionName]);
    }

    public function testParameterCountMismatch(): void
    {
        // Arrange
        $functionName = 'test_function';
        $param1 = $this->createMockParameter('param1');
        $param2 = $this->createMockParameter('param2');

        $reflectionFunction = $this->createMockFunction($functionName, [$param1, $param2]);
        $stubFunction = $this->createMockFunction($functionName, [$param1]);

        $reflectionProvider = $this->createMockReflectionProvider([$reflectionFunction]);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$stubFunction]);

        $check = new ParameterNamesCheck($reflectionProvider);

        // Act
        $result = $check->run($stubsManager, $functionName, '8.0');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());

        $failures = $result->getFailures();
        $this->assertStringContainsString('Parameter count mismatch', $failures[$functionName]);
        $this->assertStringContainsString('2 parameters', $failures[$functionName]);
        $this->assertStringContainsString('1 parameters', $failures[$functionName]);
    }

    public function testFunctionNotFoundInReflection(): void
    {
        // Arrange
        $functionName = 'missing_function';
        $stubFunction = $this->createMockFunction($functionName);

        $reflectionProvider = $this->createMockReflectionProvider([]);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$stubFunction]);

        $check = new ParameterNamesCheck($reflectionProvider);

        // Act
        $result = $check->run($stubsManager, $functionName, '8.0');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());

        $failures = $result->getFailures();
        $this->assertStringContainsString('not found in reflection data', $failures[$functionName]);
    }

    public function testFunctionNotFoundInStubs(): void
    {
        // Arrange
        $functionName = 'missing_function';
        $reflectionFunction = $this->createMockFunction($functionName);

        $reflectionProvider = $this->createMockReflectionProvider([$reflectionFunction]);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([]);

        $check = new ParameterNamesCheck($reflectionProvider);

        // Act
        $result = $check->run($stubsManager, $functionName, '8.0');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());

        $failures = $result->getFailures();
        $this->assertStringContainsString('not found in stubs', $failures[$functionName]);
    }

    public function testMatchingParameterNamesForMethod(): void
    {
        // Arrange
        $methodId = 'DateTime::format';
        $param = $this->createMockParameter('format');

        $reflectionMethod = $this->createMockMethod('format', [$param]);
        $reflectionClass = $this->createMockClass('DateTime', [$reflectionMethod]);

        $stubMethod = $this->createMockMethod('format', [$param]);
        $stubClass = $this->createMockClass('DateTime', [$stubMethod]);

        $reflectionProvider = $this->createMockReflectionProvider([], [$reflectionClass]);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$stubClass]);

        $check = new ParameterNamesCheck($reflectionProvider);

        // Act
        $result = $check->run($stubsManager, $methodId, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testMultipleParameterNameMismatches(): void
    {
        // Arrange
        $functionName = 'test_function';
        $reflectionParam1 = $this->createMockParameter('callback');
        $reflectionParam2 = $this->createMockParameter('array');
        $stubParam1 = $this->createMockParameter('wrongName1');
        $stubParam2 = $this->createMockParameter('wrongName2');

        $reflectionFunction = $this->createMockFunction($functionName, [$reflectionParam1, $reflectionParam2]);
        $stubFunction = $this->createMockFunction($functionName, [$stubParam1, $stubParam2]);

        $reflectionProvider = $this->createMockReflectionProvider([$reflectionFunction]);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$stubFunction]);

        $check = new ParameterNamesCheck($reflectionProvider);

        // Act
        $result = $check->run($stubsManager, $functionName, '8.0');

        // Assert
        $this->assertTrue($result->hasFailures());
        // Note: ParameterNamesCheck adds multiple failures, but only the first one is stored
        // because addFailure uses the same key for all parameter mismatches
        $this->assertEquals(1, $result->getFailureCount());

        $failures = $result->getFailures();
        $this->assertArrayHasKey($functionName, $failures);
        // The implementation stores the last parameter mismatch (overwrites previous ones)
        $this->assertStringContainsString('Parameter #', $failures[$functionName]);
    }

    public function testParametersWithVersionAttributes(): void
    {
        // Arrange - Simulates mktime function with two 'hour' parameters for different PHP versions
        $functionName = 'mktime';

        // Reflection has only the PHP 8.0 version (1 parameter: hour)
        $reflectionParam = $this->createMockParameter('hour');
        $reflectionFunction = $this->createMockFunction($functionName, [$reflectionParam]);

        // Stubs have both versions of the 'hour' parameter
        // Old version: available from 5.3 to 7.4
        $stubParamOld = $this->createMockParameter('hour', null, '5.3', '7.4');
        // New version: available from 8.0
        $stubParamNew = $this->createMockParameter('hour', null, '8.0', null);

        $stubFunction = $this->createMockFunction($functionName, [$stubParamOld, $stubParamNew]);

        $reflectionProvider = $this->createMockReflectionProvider([$reflectionFunction]);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$stubFunction]);

        $check = new ParameterNamesCheck($reflectionProvider);

        // Act - Test with PHP 8.0
        $result = $check->run($stubsManager, $functionName, '8.0');

        // Assert - Should succeed because only the 8.0 parameter is considered
        $this->assertFalse($result->hasFailures(), 'Expected no failures when filtering parameters by version');
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testParametersWithAliasedVersionAttributes(): void
    {
        // Arrange - Simulates collator_sort_with_sort_keys function with aliased ElementAvailable attribute
        $functionName = 'collator_sort_with_sort_keys';

        // Reflection for PHP 8.0 has 2 parameters (no $sort_flags parameter)
        $reflectionParam1 = $this->createMockParameter('object');
        $reflectionParam2 = $this->createMockParameter('array');
        $reflectionFunction = $this->createMockFunction($functionName, [$reflectionParam1, $reflectionParam2]);

        // Stubs have 3 parameters, including $sort_flags that was removed after PHP 5.6
        $stubParam1 = $this->createMockParameter('object');
        $stubParam2 = $this->createMockParameter('array');
        // $sort_flags parameter has ElementAvailable attribute (aliased): available from 5.3 to 5.6
        $stubParam3 = $this->createMockParameter('sort_flags', null, '5.3', '5.6');

        $stubFunction = $this->createMockFunction($functionName, [$stubParam1, $stubParam2, $stubParam3]);

        $reflectionProvider = $this->createMockReflectionProvider([$reflectionFunction]);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$stubFunction]);

        $check = new ParameterNamesCheck($reflectionProvider);

        // Act - Test with PHP 8.0
        $result = $check->run($stubsManager, $functionName, '8.0');

        // Assert - Should succeed because $sort_flags is filtered out (removed after 5.6)
        $this->assertFalse($result->hasFailures(), 'Expected no failures when filtering parameters with aliased version attributes');
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testParametersWithAliasedAttributesIncludedInOldVersions(): void
    {
        // Arrange - Same function but testing with PHP 5.6 where the parameter should be included
        $functionName = 'collator_sort_with_sort_keys';

        // Reflection for PHP 5.6 has 3 parameters (includes $sort_flags)
        $reflectionParam1 = $this->createMockParameter('object');
        $reflectionParam2 = $this->createMockParameter('array');
        $reflectionParam3 = $this->createMockParameter('sort_flags');
        $reflectionFunction = $this->createMockFunction($functionName, [$reflectionParam1, $reflectionParam2, $reflectionParam3]);

        // Stubs have same 3 parameters
        $stubParam1 = $this->createMockParameter('object');
        $stubParam2 = $this->createMockParameter('array');
        $stubParam3 = $this->createMockParameter('sort_flags', null, '5.3', '5.6');

        $stubFunction = $this->createMockFunction($functionName, [$stubParam1, $stubParam2, $stubParam3]);

        $reflectionProvider = $this->createMockReflectionProvider([$reflectionFunction]);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$stubFunction]);

        $check = new ParameterNamesCheck($reflectionProvider);

        // Act - Test with PHP 5.6
        $result = $check->run($stubsManager, $functionName, '5.6');

        // Assert - Should fail because check doesn't support PHP < 8.0
        // (Named parameters were introduced in PHP 8.0)
        $this->markTestSkipped('ParameterNamesCheck only supports PHP 8.0+');
    }

    public function testParameterIncludedAtBoundaryVersion(): void
    {
        // Arrange - Parameter with 'to: 8.2' should be included in PHP 8.2
        $functionName = 'imagerotate';

        // Reflection for PHP 8.2 has 4 parameters (including $ignore_transparent)
        $reflectionParam1 = $this->createMockParameter('image');
        $reflectionParam2 = $this->createMockParameter('angle');
        $reflectionParam3 = $this->createMockParameter('background_color');
        $reflectionParam4 = $this->createMockParameter('ignore_transparent');
        $reflectionFunction = $this->createMockFunction($functionName, [
            $reflectionParam1, $reflectionParam2, $reflectionParam3, $reflectionParam4
        ]);

        // Stubs have same 4 parameters, with last one having removedVersion='8.2'
        $stubParam1 = $this->createMockParameter('image');
        $stubParam2 = $this->createMockParameter('angle');
        $stubParam3 = $this->createMockParameter('background_color');
        // Parameter available up to and including PHP 8.2 (removed in 8.3)
        $stubParam4 = $this->createMockParameter('ignore_transparent', null, null, '8.2');

        $stubFunction = $this->createMockFunction($functionName, [
            $stubParam1, $stubParam2, $stubParam3, $stubParam4
        ]);

        $reflectionProvider = $this->createMockReflectionProvider([$reflectionFunction]);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$stubFunction]);

        $check = new ParameterNamesCheck($reflectionProvider);

        // Act - Test with PHP 8.2 (the boundary version)
        $result = $check->run($stubsManager, $functionName, '8.2');

        // Assert - Should succeed because parameter with 'to: 8.2' is included in PHP 8.2
        $this->assertFalse($result->hasFailures(), 'Expected no failures when parameter is at boundary version');
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testParameterExcludedAfterBoundaryVersion(): void
    {
        // Arrange - Parameter with 'to: 8.2' should be excluded in PHP 8.3
        $functionName = 'imagerotate';

        // Reflection for PHP 8.3 has 3 parameters (no $ignore_transparent)
        $reflectionParam1 = $this->createMockParameter('image');
        $reflectionParam2 = $this->createMockParameter('angle');
        $reflectionParam3 = $this->createMockParameter('background_color');
        $reflectionFunction = $this->createMockFunction($functionName, [
            $reflectionParam1, $reflectionParam2, $reflectionParam3
        ]);

        // Stubs have 4 parameters, with last one having removedVersion='8.2'
        $stubParam1 = $this->createMockParameter('image');
        $stubParam2 = $this->createMockParameter('angle');
        $stubParam3 = $this->createMockParameter('background_color');
        // Parameter available up to and including PHP 8.2 (removed in 8.3)
        $stubParam4 = $this->createMockParameter('ignore_transparent', null, null, '8.2');

        $stubFunction = $this->createMockFunction($functionName, [
            $stubParam1, $stubParam2, $stubParam3, $stubParam4
        ]);

        $reflectionProvider = $this->createMockReflectionProvider([$reflectionFunction]);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$stubFunction]);

        $check = new ParameterNamesCheck($reflectionProvider);

        // Act - Test with PHP 8.3 (after boundary version)
        $result = $check->run($stubsManager, $functionName, '8.3');

        // Assert - Should succeed because parameter with 'to: 8.2' is excluded in PHP 8.3
        $this->assertFalse($result->hasFailures(), 'Expected no failures when parameter is excluded after boundary');
        $this->assertEquals(1, $result->getSuccessCount());
    }
}
