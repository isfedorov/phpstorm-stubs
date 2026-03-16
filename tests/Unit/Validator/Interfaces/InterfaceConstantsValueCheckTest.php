<?php

namespace StubTests\Unit\Validator\Interfaces;

use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Interfaces\InterfaceConstantsValueCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class InterfaceConstantsValueCheckTest extends CheckTestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeConstant(string $name, mixed $value = null): PHPClassConstant
    {
        $constant = new PHPClassConstant();
        $constant->setName($name);
        $constant->value = $value;
        return $constant;
    }

    private function makeInterface(string $id, array $constants = []): PHPInterface
    {
        $interface = new PHPInterface();
        $interface->setId($id);
        $interface->setConstants($constants);
        return $interface;
    }

    private function createMockReflectionProviderWithInterfaces(array $interfaces = []): ReflectionProviderInterface
    {
        $provider = $this->createMock(ReflectionProviderInterface::class);
        $manager  = $this->createMockStorageManager();
        $manager->method('getInterfaces')->willReturn($interfaces);
        $provider->method('getReflection')->willReturn($manager);
        return $provider;
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsAllVersions(): void
    {
        $check = new InterfaceConstantsValueCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_5_6->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Entity not found ──────────────────────────────────────────────────────

    public function testInterfaceNotFoundInReflectionFails(): void
    {
        $ifaceId   = '\\Countable';
        $stubIface = $this->makeInterface($ifaceId);

        $provider = $this->createMockReflectionProviderWithInterfaces([]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsValueCheck($provider))->run($stubs, $ifaceId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$ifaceId]);
        $this->assertStringContainsString('Interface', $result->getFailures()[$ifaceId]);
        $this->assertStringNotContainsString('Class', $result->getFailures()[$ifaceId]);
    }

    public function testInterfaceNotFoundInStubsFails(): void
    {
        $ifaceId   = '\\Countable';
        $reflIface = $this->makeInterface($ifaceId);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([]);

        $result = (new InterfaceConstantsValueCheck($provider))->run($stubs, $ifaceId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in stubs', $result->getFailures()[$ifaceId]);
    }

    // ── Matching values ───────────────────────────────────────────────────────

    public function testMatchingValuesPasses(): void
    {
        $ifaceId   = '\\MyInterface';
        $reflIface = $this->makeInterface($ifaceId, [$this->makeConstant('VERSION', 1)]);
        $stubIface = $this->makeInterface($ifaceId, [$this->makeConstant('VERSION', 1)]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsValueCheck($provider))->run($stubs, $ifaceId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Value mismatch ────────────────────────────────────────────────────────

    public function testValueMismatchFailsOnLatestPhp(): void
    {
        $ifaceId   = '\\MyInterface';
        $reflIface = $this->makeInterface($ifaceId, [$this->makeConstant('VERSION', 1)]);
        $stubIface = $this->makeInterface($ifaceId, [$this->makeConstant('VERSION', 99)]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsValueCheck($provider))->run($stubs, $ifaceId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($ifaceId . '::VERSION', $failures);
        $this->assertStringContainsString('value mismatch', $failures[$ifaceId . '::VERSION']);
        $this->assertStringNotContainsString('Class', $failures[$ifaceId . '::VERSION']);
    }

    public function testValueMismatchSkippedOnNonLatestPhp(): void
    {
        $ifaceId   = '\\MyInterface';
        $reflIface = $this->makeInterface($ifaceId, [$this->makeConstant('VERSION', 1)]);
        $stubIface = $this->makeInterface($ifaceId, [$this->makeConstant('VERSION', 99)]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsValueCheck($provider))->run($stubs, $ifaceId, PhpVersions::PHP_8_0->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Constant not in reflection is skipped ─────────────────────────────────

    public function testConstantNotInReflectionIsSkipped(): void
    {
        $ifaceId   = '\\MyInterface';
        $reflIface = $this->makeInterface($ifaceId); // no constants in reflection
        $stubIface = $this->makeInterface($ifaceId, [$this->makeConstant('VERSION', 99)]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsValueCheck($provider))->run($stubs, $ifaceId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }
}
