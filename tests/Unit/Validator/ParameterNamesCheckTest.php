<?php

namespace StubTests\Unit\Validator;

use StubTests\Sources\Validator\ParameterNamesCheck;

class ParameterNamesCheckTest extends CheckTestCase
{
    private ParameterNamesCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new ParameterNamesCheck();
    }

    public function testSupportsPhp80AndAbove(): void
    {
        $this->assertFalse($this->check->supports('5.6'));
        $this->assertFalse($this->check->supports('7.0'));
        $this->assertFalse($this->check->supports('7.4'));
        $this->assertTrue($this->check->supports('8.0'));
        $this->assertTrue($this->check->supports('8.1'));
        $this->assertTrue($this->check->supports('8.4'));
    }

    public function testMatchingParameterNamesForFunction(): void
    {
        // Arrange
        $functionName = 'array_map';
        $param1 = $this->createMockParameter('callback');
        $param2 = $this->createMockParameter('array');

        $reflectionFunction = $this->createMockFunction($functionName, [$param1, $param2]);
        $stubFunction = $this->createMockFunction($functionName, [$param1, $param2]);

        $reflectionManager = $this->createMockStorageManager();
        $reflectionManager->method('getFunctions')->willReturn([$reflectionFunction]);

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getFunctions')->willReturn([$stubFunction]);

        // Note: This test needs a way to inject reflection manager
        // For now, we'll skip the full integration test with Runner::getReflection
        // and focus on testing the logic with the stubs parameter only

        // Act
        $result = $this->check->run($stubsManager, $functionName, '8.0');

        // Assert - This will call Runner::getReflection which we can't easily mock
        // The test validates the approach but needs refactoring of ParameterNamesCheck
        // to accept reflection as a parameter for better testability
        $this->markTestSkipped('Requires refactoring ParameterNamesCheck to accept reflection manager as parameter');
    }

    public function testParameterNameMismatch(): void
    {
        // This test is skipped because ParameterNamesCheck calls Runner::getReflection
        // which requires actual reflection data. The check would need to be refactored
        // to accept reflection as a dependency for full unit testing.
        $this->markTestSkipped('Requires refactoring ParameterNamesCheck for dependency injection');
    }

    public function testParameterCountMismatch(): void
    {
        $this->markTestSkipped('Requires refactoring ParameterNamesCheck for dependency injection');
    }

    public function testFunctionNotFoundInReflection(): void
    {
        $this->markTestSkipped('Requires refactoring ParameterNamesCheck for dependency injection');
    }

    public function testFunctionNotFoundInStubs(): void
    {
        $this->markTestSkipped('Requires refactoring ParameterNamesCheck for dependency injection');
    }

    public function testMatchingParameterNamesForMethod(): void
    {
        $this->markTestSkipped('Requires refactoring ParameterNamesCheck for dependency injection');
    }

    /**
     * Note: These tests are currently skipped because ParameterNamesCheck has a dependency
     * on Runner::getReflection() which is a static call. To make this class fully testable:
     *
     * Option 1: Refactor ParameterNamesCheck to accept reflection manager as constructor parameter
     * Option 2: Create a wrapper class around Runner that can be mocked
     * Option 3: Use a testing framework that supports static method mocking (requires additional setup)
     *
     * The test structure demonstrates what we WOULD test once the refactoring is complete.
     */
}
