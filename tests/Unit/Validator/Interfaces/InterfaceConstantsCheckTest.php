<?php

namespace StubTests\Unit\Validator\Interfaces;

use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Interfaces\InterfaceConstantsCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class InterfaceConstantsCheckTest extends CheckTestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeConstant(string $name, mixed $value = null, string $visibility = 'public'): PHPClassConstant
    {
        $constant = new PHPClassConstant();
        $constant->setName($name);
        $constant->value = $value;
        $constant->visibility = $visibility;
        return $constant;
    }

    private function makeInterface(string $id, array $constants = []): PHPInterface
    {
        $interface = new PHPInterface();
        $interface->setId($id);
        $interface->constants = $constants;
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
        $check = new InterfaceConstantsCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_5_6->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Matching constants ────────────────────────────────────────────────────

    public function testMatchingConstantPasses(): void
    {
        $ifaceId   = '\\Countable';
        $reflIface = $this->makeInterface($ifaceId, [$this->makeConstant('MODE', 1)]);
        $stubIface = $this->makeInterface($ifaceId, [$this->makeConstant('MODE', 1)]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsCheck($provider))->run($stubs, $ifaceId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Spurious constant in stubs ────────────────────────────────────────────

    public function testSpuriousConstantInStubsFails(): void
    {
        $ifaceId   = '\\Countable';
        $reflIface = $this->makeInterface($ifaceId); // no constants in reflection
        $stubIface = $this->makeInterface($ifaceId, [$this->makeConstant('GHOST', 1)]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsCheck($provider))->run($stubs, $ifaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($ifaceId . '::GHOST', $failures);
        $this->assertStringContainsString('not found in reflection', $failures[$ifaceId . '::GHOST']);
        $this->assertStringNotContainsString('Class', $failures[$ifaceId . '::GHOST']);
    }

    public function testConstantInReflectionOnlyPasses(): void
    {
        // Constant only in reflection (not stub) is fine — might be inherited in stubs
        $ifaceId   = '\\Countable';
        $reflIface = $this->makeInterface($ifaceId, [$this->makeConstant('MODE', 1)]);
        $stubIface = $this->makeInterface($ifaceId);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsCheck($provider))->run($stubs, $ifaceId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Value mismatch ────────────────────────────────────────────────────────

    public function testValueMismatchFails(): void
    {
        $ifaceId   = '\\Countable';
        $reflIface = $this->makeInterface($ifaceId, [$this->makeConstant('MODE', 1)]);
        $stubIface = $this->makeInterface($ifaceId, [$this->makeConstant('MODE', 2)]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsCheck($provider))->run($stubs, $ifaceId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($ifaceId . '::MODE', $failures);
        $this->assertStringContainsString('Value mismatch', $failures[$ifaceId . '::MODE']);
        $this->assertStringNotContainsString('Class', $failures[$ifaceId . '::MODE']);
    }
}
