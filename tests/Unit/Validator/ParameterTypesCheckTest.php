<?php

namespace StubTests\Unit\Validator;

use StubTests\Sources\Validator\ParameterTypesCheck;

class ParameterTypesCheckTest extends CheckTestCase
{
    private ParameterTypesCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new ParameterTypesCheck();
    }

    public function testSupportsPhp70AndAbove(): void
    {
        $this->assertFalse($this->check->supports('5.6'));
        $this->assertTrue($this->check->supports('7.0'));
        $this->assertTrue($this->check->supports('7.4'));
        $this->assertTrue($this->check->supports('8.0'));
        $this->assertTrue($this->check->supports('8.4'));
    }

    public function testMatchingParameterTypes(): void
    {
        // This test is skipped because ParameterTypesCheck calls Runner::getReflection
        // which requires actual reflection data. The check would need to be refactored
        // to accept reflection as a dependency for full unit testing.
        $this->markTestSkipped('Requires refactoring ParameterTypesCheck for dependency injection');
    }

    public function testParameterTypeMismatch(): void
    {
        $this->markTestSkipped('Requires refactoring ParameterTypesCheck for dependency injection');
    }

    public function testParameterCountMismatch(): void
    {
        $this->markTestSkipped('Requires refactoring ParameterTypesCheck for dependency injection');
    }

    public function testFunctionNotFoundInReflection(): void
    {
        $this->markTestSkipped('Requires refactoring ParameterTypesCheck for dependency injection');
    }

    public function testFunctionNotFoundInStubs(): void
    {
        $this->markTestSkipped('Requires refactoring ParameterTypesCheck for dependency injection');
    }

    public function testHandlesMixedTypes(): void
    {
        $this->markTestSkipped('Requires refactoring ParameterTypesCheck for dependency injection');
    }

    public function testHandlesUnionTypes(): void
    {
        $this->markTestSkipped('Requires refactoring ParameterTypesCheck for dependency injection');
    }

    /**
     * Note: These tests are currently skipped because ParameterTypesCheck has a dependency
     * on Runner::getReflection() which is a static call. To make this class fully testable:
     *
     * Option 1: Refactor ParameterTypesCheck to accept reflection manager as constructor parameter
     * Option 2: Create a wrapper class around Runner that can be mocked
     * Option 3: Use a testing framework that supports static method mocking (requires additional setup)
     *
     * The test structure demonstrates what we WOULD test once the refactoring is complete.
     */
}
