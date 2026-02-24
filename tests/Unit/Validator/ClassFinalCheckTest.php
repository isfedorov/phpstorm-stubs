<?php

namespace StubTests\Unit\Validator;

use StubTests\Sources\Validator\ClassFinalCheck;

class ClassFinalCheckTest extends CheckTestCase
{
    private ClassFinalCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new ClassFinalCheck();
    }

    public function testSupportsAllPhpVersions(): void
    {
        $this->assertTrue($this->check->supports('5.6'));
        $this->assertTrue($this->check->supports('7.0'));
        $this->assertTrue($this->check->supports('8.0'));
        $this->assertTrue($this->check->supports('8.4'));
    }

    public function testFinalClassWithBooleanTrue(): void
    {
        // Arrange
        $className = 'MyFinalClass';

        $stubClass = $this->createMockClassWithProperties($className, null, true, null);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$stubClass]);

        // Act
        $result = $this->check->run($stubsManager, $className, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testNonFinalClassWithBooleanFalse(): void
    {
        // Arrange
        $className = 'RegularClass';

        $stubClass = $this->createMockClassWithProperties($className, null, false, null);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$stubClass]);

        // Act
        $result = $this->check->run($stubsManager, $className, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testClassWithoutFinalProperty(): void
    {
        // Arrange
        $className = 'ClassWithoutFinal';

        // Don't set isFinal property (null)
        $stubClass = $this->createMockClassWithProperties($className, null, null, null);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$stubClass]);

        // Act
        $result = $this->check->run($stubsManager, $className, '8.0');

        // Assert - Should succeed because null/unset is treated as false
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testClassNotFoundInStubs(): void
    {
        // Arrange
        $className = 'MissingClass';

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([]);

        // Act
        $result = $this->check->run($stubsManager, $className, '8.0');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());

        $failures = $result->getFailures();
        $this->assertArrayHasKey($className, $failures);
        $this->assertStringContainsString('not found in stubs', $failures[$className]);
    }

    public function testInvalidFinalPropertyType(): void
    {
        // Arrange
        $className = 'InvalidClass';

        $stubClass = $this->createMockClassWithProperties($className);
        // Set invalid type for isFinal (string instead of boolean)
        $stubClass->isFinal = 'yes'; // Invalid type

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$stubClass]);

        // Act
        $result = $this->check->run($stubsManager, $className, '8.0');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());

        $failures = $result->getFailures();
        $this->assertArrayHasKey($className, $failures);
        $this->assertStringContainsString('invalid isFinal property type', $failures[$className]);
        $this->assertStringContainsString('expected boolean', $failures[$className]);
    }

    public function testFinalClassInNamespace(): void
    {
        // Arrange
        $className = 'Foo\\Bar\\FinalClass';

        $stubClass = $this->createMockClassWithProperties($className, 'Foo\\Bar', true, null);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$stubClass]);

        // Act
        $result = $this->check->run($stubsManager, $className, '7.4');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }
}
