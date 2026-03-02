<?php

namespace StubTests\Unit\Validator\Classes;

use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Classes\MethodExistsCheck;
use StubTests\Unit\Validator\CheckTestCase;

class MethodExistsCheckTest extends CheckTestCase
{
    private MethodExistsCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new MethodExistsCheck();
    }

    public function testSupportsAllPhpVersions(): void
    {
        $this->assertTrue($this->check->supports(PhpVersions::EARLIEST->value));
        $this->assertTrue($this->check->supports(PhpVersions::PHP_7_0->value));
        $this->assertTrue($this->check->supports(PhpVersions::PHP_8_0->value));
        $this->assertTrue($this->check->supports(PhpVersions::LATEST->value));
    }

    public function testMethodExistsInClass(): void
    {
        // Arrange
        $methodId = 'DateTime::format';
        $mockMethod = $this->createMockMethod('format');
        $mockClass = $this->createMockClass('DateTime', [$mockMethod]);

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$mockClass]);

        // Act
        $result = $this->check->run($stubsManager, $methodId, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testMethodNotFoundInClass(): void
    {
        // Arrange
        $methodId = 'DateTime::missingMethod';
        $mockMethod = $this->createMockMethod('format');
        $mockClass = $this->createMockClass('DateTime', [$mockMethod]);

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$mockClass]);

        // Act
        $result = $this->check->run($stubsManager, $methodId, '8.0');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());

        $failures = $result->getFailures();
        $this->assertArrayHasKey($methodId, $failures);
        $this->assertStringContainsString('exists in PHP 8.0 but not in stubs', $failures[$methodId]);
    }

    public function testClassNotFoundInStubs(): void
    {
        // Arrange
        $methodId = 'MissingClass::method';
        $mockClass = $this->createMockClass('DateTime');

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$mockClass]);

        // Act
        $result = $this->check->run($stubsManager, $methodId, '8.0');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());

        $failures = $result->getFailures();
        $this->assertArrayHasKey($methodId, $failures);
        $this->assertStringContainsString('Class MissingClass not found in stubs', $failures[$methodId]);
    }

    public function testInvalidMethodIdFormat(): void
    {
        // Arrange
        $methodId = 'InvalidFormatNoDoubleColon';

        $stubsManager = $this->createMockStorageManager();

        // Act
        $result = $this->check->run($stubsManager, $methodId, '8.0');

        // Assert
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(1, $result->getFailureCount());

        $failures = $result->getFailures();
        $this->assertArrayHasKey($methodId, $failures);
        $this->assertStringContainsString('Invalid method ID format', $failures[$methodId]);
    }

    public function testNamespacedClassAndMethod(): void
    {
        // Arrange
        $methodId = '\\Namespace\\MyClass::myMethod';
        $mockMethod = $this->createMockMethod('myMethod');
        $mockClass = $this->createMockClass('\\Namespace\\MyClass', [$mockMethod]);

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$mockClass]);

        // Act
        $result = $this->check->run($stubsManager, $methodId, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testMethodExistsAmongMultipleMethods(): void
    {
        // Arrange
        $methodId = 'DateTime::diff';
        $mockMethods = [
            $this->createMockMethod('format'),
            $this->createMockMethod('diff'),
            $this->createMockMethod('modify'),
        ];
        $mockClass = $this->createMockClass('DateTime', $mockMethods);

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$mockClass]);

        // Act
        $result = $this->check->run($stubsManager, $methodId, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testMethodFoundByGetIdForClass(): void
    {
        // Arrange
        $methodId = 'TestClass::testMethod';
        $mockMethod = $this->createMockMethod('testMethod');

        // Mock class with both getId and getName
        $mockClass = $this->createMock(\StubTests\Sources\Parsers\Entities\Model\PHPClass::class);
        $mockClass->method('getId')->willReturn('TestClass');
        $mockClass->method('getName')->willReturn('DifferentName');
        $mockClass->method('getMethods')->willReturn([$mockMethod]);

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$mockClass]);

        // Act
        $result = $this->check->run($stubsManager, $methodId, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testMethodFoundByGetNameForClass(): void
    {
        // Arrange
        $methodId = 'TestClass::testMethod';
        $mockMethod = $this->createMockMethod('testMethod');

        // Mock class where getId doesn't match but getName does
        $mockClass = $this->createMock(\StubTests\Sources\Parsers\Entities\Model\PHPClass::class);
        $mockClass->method('getId')->willReturn('DifferentId');
        $mockClass->method('getName')->willReturn('TestClass');
        $mockClass->method('getMethods')->willReturn([$mockMethod]);

        $stubsManager = $this->createMockStorageManager();
        $stubsManager->method('getClasses')->willReturn([$mockClass]);

        // Act
        $result = $this->check->run($stubsManager, $methodId, '8.0');

        // Assert
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }
}
