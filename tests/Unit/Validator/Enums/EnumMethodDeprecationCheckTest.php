<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumMethodDeprecationCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class EnumMethodDeprecationCheckTest extends CheckTestCase
{
    private function makeMethod(string $name, bool $isDeprecated = false): PHPMethod
    {
        $method = new PHPMethod();
        $method->setName($name);
        $method->setDeprecated($isDeprecated);
        return $method;
    }

    private function makeEnum(string $id, array $methods = []): PHPEnum
    {
        $enum = new PHPEnum();
        $enum->setId($id);
        $enum->setMethods($methods);
        return $enum;
    }

    private function createMockReflectionProviderWithEnums(array $enums = []): ReflectionProviderInterface
    {
        $provider = $this->createMock(ReflectionProviderInterface::class);
        $manager  = $this->createMockStorageManager();
        $manager->method('getEnums')->willReturn($enums);
        $provider->method('getReflection')->willReturn($manager);
        return $provider;
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsAllPhpVersions(): void
    {
        $check = new EnumMethodDeprecationCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Non-deprecated method ─────────────────────────────────────────────────

    public function testNonDeprecatedMethodPassesWithNonDeprecatedStub(): void
    {
        $enumId   = '\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', false)]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', false)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodDeprecationCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    // ── Deprecated method ─────────────────────────────────────────────────────

    public function testDeprecatedReflectionMethodRequiresDeprecatedStub(): void
    {
        $enumId   = '\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', true)]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', false)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodDeprecationCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($enumId . '::cases', $failures);
        $this->assertStringContainsString('deprecated', $failures[$enumId . '::cases']);
        $this->assertStringNotContainsString('Class', $failures[$enumId . '::cases']);
    }

    public function testDeprecatedStubWithNonDeprecatedReflectionPasses(): void
    {
        $enumId   = '\RoundingMode';
        // Reflection says NOT deprecated, stub says deprecated → OK (one-directional)
        $reflEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', false)]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', true)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodDeprecationCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }
}
