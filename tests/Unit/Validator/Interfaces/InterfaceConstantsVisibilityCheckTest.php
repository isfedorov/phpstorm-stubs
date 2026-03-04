<?php

namespace StubTests\Unit\Validator\Interfaces;

use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Interfaces\InterfaceConstantsVisibilityCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class InterfaceConstantsVisibilityCheckTest extends CheckTestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeConstant(string $name, string $visibility = 'public'): PHPClassConstant
    {
        $constant = new PHPClassConstant();
        $constant->setName($name);
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

    // ── Entity not found ──────────────────────────────────────────────────────

    public function testInterfaceNotFoundInReflectionFails(): void
    {
        $ifaceId  = '\\Countable';
        $stubIface = $this->makeInterface($ifaceId);

        $provider = $this->createMockReflectionProviderWithInterfaces([]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsVisibilityCheck($provider))->run($stubs, $ifaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$ifaceId]);
        $this->assertStringContainsString('Interface', $result->getFailures()[$ifaceId]);
    }

    public function testInterfaceNotFoundInStubsFails(): void
    {
        $ifaceId   = '\\Countable';
        $reflIface = $this->makeInterface($ifaceId);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([]);

        $result = (new InterfaceConstantsVisibilityCheck($provider))->run($stubs, $ifaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in stubs', $result->getFailures()[$ifaceId]);
    }

    // ── Matching visibility ───────────────────────────────────────────────────

    public function testMatchingPublicVisibilityPasses(): void
    {
        $ifaceId   = '\\MyInterface';
        $reflIface = $this->makeInterface($ifaceId, [$this->makeConstant('VERSION', 'public')]);
        $stubIface = $this->makeInterface($ifaceId, [$this->makeConstant('VERSION', 'public')]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsVisibilityCheck($provider))->run($stubs, $ifaceId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testNoConstantsPasses(): void
    {
        $ifaceId   = '\\EmptyInterface';
        $reflIface = $this->makeInterface($ifaceId);
        $stubIface = $this->makeInterface($ifaceId);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsVisibilityCheck($provider))->run($stubs, $ifaceId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Visibility mismatch ───────────────────────────────────────────────────

    public function testVisibilityMismatchFails(): void
    {
        // Interface constants are normally always public in PHP; a stub error could annotate
        // a constant as protected — the check should catch it.
        $ifaceId   = '\\MyInterface';
        $reflIface = $this->makeInterface($ifaceId, [$this->makeConstant('VERSION', 'public')]);
        $stubIface = $this->makeInterface($ifaceId, [$this->makeConstant('VERSION', 'protected')]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsVisibilityCheck($provider))->run($stubs, $ifaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($ifaceId . '::VERSION', $failures);
        $this->assertStringContainsString("'public'", $failures[$ifaceId . '::VERSION']);
        $this->assertStringContainsString("'protected'", $failures[$ifaceId . '::VERSION']);
    }

    // ── Constant not in reflection is skipped ─────────────────────────────────

    public function testConstantNotInReflectionIsSkipped(): void
    {
        $ifaceId   = '\\MyInterface';
        $reflIface = $this->makeInterface($ifaceId); // no constants in reflection
        $stubIface = $this->makeInterface($ifaceId, [$this->makeConstant('EXTRA', 'protected')]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceConstantsVisibilityCheck($provider))->run($stubs, $ifaceId, '8.0');

        $this->assertFalse($result->hasFailures());
    }
}
