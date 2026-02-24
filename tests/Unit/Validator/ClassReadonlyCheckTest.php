<?php

namespace StubTests\Unit\Validator;

use StubTests\Sources\Validator\ClassReadonlyCheck;

class ClassReadonlyCheckTest extends CheckTestCase
{
    private ClassReadonlyCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new ClassReadonlyCheck();
    }

    public function testSupportsPhp82AndAbove(): void
    {
        $this->assertTrue($this->check->supports('8.2'));
        $this->assertTrue($this->check->supports('8.3'));
        $this->assertTrue($this->check->supports('8.4'));
    }

    public function testDoesNotSupportOlderPhpVersions(): void
    {
        $this->assertFalse($this->check->supports('5.6'));
        $this->assertFalse($this->check->supports('7.0'));
        $this->assertFalse($this->check->supports('7.4'));
        $this->assertFalse($this->check->supports('8.0'));
        $this->assertFalse($this->check->supports('8.1'));
    }

    public function testReadonlyClassWithBooleanTrue(): void
    {
        // Arrange
        $className = 'MyReadonlyClass';

        $stubClass = $this->createMockClassWithProperties($className, null, null, true);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$stubClass]);

        // Act
        $result = $this->check->run($stubsManager, $className, '8.2');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testNonReadonlyClassWithBooleanFalse(): void
    {
        // Arrange
        $className = 'RegularClass';

        $stubClass = $this->createMockClassWithProperties($className, null, null, false);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$stubClass]);

        // Act
        $result = $this->check->run($stubsManager, $className, '8.2');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testClassWithoutReadonlyProperty(): void
    {
        // Arrange
        $className = 'ClassWithoutReadonly';

        // Don't set isReadonly property (null)
        $stubClass = $this->createMockClassWithProperties($className, null, null, null);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$stubClass]);

        // Act
        $result = $this->check->run($stubsManager, $className, '8.2');

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
        $result = $this->check->run($stubsManager, $className, '8.2');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());

        $failures = $result->getFailures();
        $this->assertArrayHasKey($className, $failures);
        $this->assertStringContainsString('not found in stubs', $failures[$className]);
    }

    public function testInvalidReadonlyPropertyType(): void
    {
        // Arrange
        $className = 'InvalidClass';

        $stubClass = $this->createMockClassWithProperties($className);
        // Set invalid type for isReadonly (string instead of boolean)
        $stubClass->isReadonly = 'yes'; // Invalid type

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$stubClass]);

        // Act
        $result = $this->check->run($stubsManager, $className, '8.2');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());

        $failures = $result->getFailures();
        $this->assertArrayHasKey($className, $failures);
        $this->assertStringContainsString('invalid isReadonly property type', $failures[$className]);
        $this->assertStringContainsString('expected boolean', $failures[$className]);
    }

    public function testReadonlyClassInNamespace(): void
    {
        // Arrange
        $className = 'Foo\\Bar\\ReadonlyClass';

        $stubClass = $this->createMockClassWithProperties($className, 'Foo\\Bar', null, true);
        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$stubClass]);

        // Act
        $result = $this->check->run($stubsManager, $className, '8.3');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }
}
