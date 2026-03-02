<?php

namespace StubTests\Unit\Validator\Functions;

use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Functions\FunctionExistsCheck;
use StubTests\Unit\Validator\CheckTestCase;

class FunctionExistsCheckTest extends CheckTestCase
{
    private FunctionExistsCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new FunctionExistsCheck();
    }

    public function testSupportsAllPhpVersions(): void
    {
        $this->assertTrue($this->check->supports(PhpVersions::EARLIEST->value));
        $this->assertTrue($this->check->supports(PhpVersions::PHP_7_0->value));
        $this->assertTrue($this->check->supports(PhpVersions::PHP_8_0->value));
        $this->assertTrue($this->check->supports(PhpVersions::LATEST->value));
    }

    public function testFunctionExistsInStubs(): void
    {
        // Arrange
        $functionName = 'array_map';
        $mockFunction = $this->createMockFunction($functionName);

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$mockFunction]);

        // Act
        $result = $this->check->run($stubsManager, $functionName, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
        $this->assertCount(1, $result->getSuccesses());
    }

    public function testFunctionNotFoundInStubs(): void
    {
        // Arrange
        $functionName = 'missing_function';
        $mockFunction = $this->createMockFunction('array_map');

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$mockFunction]);

        // Act
        $result = $this->check->run($stubsManager, $functionName, '8.0');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());
        $this->assertEquals(0, $result->getSuccessCount());

        $failures = $result->getFailures();
        $this->assertArrayHasKey($functionName, $failures);
        $this->assertStringContainsString('exists in PHP 8.0 but not in stubs', $failures[$functionName]);
    }

    public function testFunctionExistsAmongMultipleFunctions(): void
    {
        // Arrange
        $functionName = 'array_filter';
        $mockFunctions = [
            $this->createMockFunction('array_map'),
            $this->createMockFunction('array_filter'),
            $this->createMockFunction('array_reduce'),
        ];

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn($mockFunctions);

        // Act
        $result = $this->check->run($stubsManager, $functionName, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testEmptyFunctionsArray(): void
    {
        // Arrange
        $functionName = 'any_function';

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([]);

        // Act
        $result = $this->check->run($stubsManager, $functionName, '8.0');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());
    }

    public function testFunctionFoundByGetIdMethod(): void
    {
        // Arrange
        $functionName = 'test_function';

        // Create a mock that has getId but not getName
        $mockFunction = $this->createMock(\StubTests\Sources\Parsers\Entities\Model\PHPFunction::class);
        $mockFunction->method('getId')->willReturn($functionName);

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$mockFunction]);

        // Act
        $result = $this->check->run($stubsManager, $functionName, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testFunctionFoundByGetNameMethod(): void
    {
        // Arrange
        $functionName = 'test_function';

        // Create a mock that returns different getId but matching getName
        $mockFunction = $this->createMock(\StubTests\Sources\Parsers\Entities\Model\PHPFunction::class);
        $mockFunction->method('getId')->willReturn('different_id');
        $mockFunction->method('getName')->willReturn($functionName);

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$mockFunction]);

        // Act
        $result = $this->check->run($stubsManager, $functionName, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }
}
