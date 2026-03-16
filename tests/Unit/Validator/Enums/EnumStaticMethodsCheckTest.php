<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumStaticMethodsCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class EnumStaticMethodsCheckTest extends CheckTestCase
{
    private function makeReflectionMethod(string $name, bool $isStatic): PHPMethod
    {
        $method = new PHPMethod();
        $method->setName($name);
        $method->setIsStatic($isStatic);
        return $method;
    }

    private function makeStubMethod(string $name, bool $isStatic): PHPMethod
    {
        $method = new PHPMethod();
        $method->setName($name);
        $method->setIsStatic($isStatic);
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
        $check = new EnumStaticMethodsCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Matching static flag ──────────────────────────────────────────────────

    public function testStaticMethodMatchPasses(): void
    {
        $enumId   = '\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeReflectionMethod('cases', true)]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeStubMethod('cases', true)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumStaticMethodsCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    // ── Mismatch ──────────────────────────────────────────────────────────────

    public function testStaticMethodMismatchFails(): void
    {
        $enumId   = '\RoundingMode';
        // Reflection says static, stub says non-static
        $reflEnum = $this->makeEnum($enumId, [$this->makeReflectionMethod('cases', true)]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeStubMethod('cases', false)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumStaticMethodsCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($enumId . '::cases', $failures);
        $this->assertStringContainsString('static', $failures[$enumId . '::cases']);
    }

    public function testErrorMessageContainsEnumNotClass(): void
    {
        $enumId   = '\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeReflectionMethod('cases', true)]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeStubMethod('cases', false)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumStaticMethodsCheck($provider))->run($stubs, $enumId, '8.1');

        $failures = $result->getFailures();
        $message  = $failures[$enumId . '::cases'];
        $this->assertStringNotContainsString('Class', $message);
    }
}
