<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumConstantsCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class EnumConstantsCheckTest extends CheckTestCase
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

    private function makeEnum(string $id, array $constants = []): PHPEnum
    {
        $enum = new PHPEnum();
        $enum->setId($id);
        $enum->setConstants($constants);
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

    public function testSupportsAllVersions(): void
    {
        $check = new EnumConstantsCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Matching constants ────────────────────────────────────────────────────

    public function testMatchingConstantPasses(): void
    {
        $enumId   = '\\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', 0)]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', 0)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    public function testNoConstantsPasses(): void
    {
        $enumId   = '\\RoundingMode';
        $reflEnum = $this->makeEnum($enumId);
        $stubEnum = $this->makeEnum($enumId);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    // ── Spurious constant in stubs ────────────────────────────────────────────

    public function testSpuriousConstantInStubsFails(): void
    {
        $enumId   = '\\RoundingMode';
        $reflEnum = $this->makeEnum($enumId); // no constants in reflection
        $stubEnum = $this->makeEnum($enumId, [$this->makeConstant('Ghost', 0)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($enumId . '::Ghost', $failures);
        $this->assertStringContainsString('not found in reflection', $failures[$enumId . '::Ghost']);
        $this->assertStringNotContainsString('Class', $failures[$enumId . '::Ghost']);
    }

    public function testConstantInReflectionOnlyPasses(): void
    {
        // Constant only in reflection (not stub) is fine — might be inherited in stubs
        $enumId   = '\\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', 0)]);
        $stubEnum = $this->makeEnum($enumId);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    // ── Visibility is not checked here ───────────────────────────────────────

    public function testVisibilityMismatchNotCheckedByThisCheck(): void
    {
        // Visibility is validated by EnumConstantsVisibilityCheck, not here.
        $enumId   = '\\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', null, 'public')]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', null, 'protected')]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    // ── Value is not checked here ─────────────────────────────────────────────

    public function testValueMismatchNotCheckedByThisCheck(): void
    {
        // Value comparison is handled by EnumConstantsValueCheck, not here.
        $enumId   = '\\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', 0)]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', 99)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsCheck($provider))->run($stubs, $enumId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }
}
