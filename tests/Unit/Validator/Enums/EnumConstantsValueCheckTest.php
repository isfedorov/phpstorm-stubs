<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumConstantsValueCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class EnumConstantsValueCheckTest extends CheckTestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeConstant(string $name, mixed $value = null): PHPClassConstant
    {
        $constant = new PHPClassConstant();
        $constant->setName($name);
        $constant->value = $value;
        return $constant;
    }

    private function makeEnum(string $id, array $constants = []): PHPEnum
    {
        $enum = new PHPEnum();
        $enum->setId($id);
        $enum->constants = $constants;
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
        $check = new EnumConstantsValueCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Entity not found ──────────────────────────────────────────────────────

    public function testEnumNotFoundInReflectionFails(): void
    {
        $enumId   = '\\RoundingMode';
        $stubEnum = $this->makeEnum($enumId);

        $provider = $this->createMockReflectionProviderWithEnums([]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsValueCheck($provider))->run($stubs, $enumId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$enumId]);
        $this->assertStringContainsString('Enum', $result->getFailures()[$enumId]);
        $this->assertStringNotContainsString('Class', $result->getFailures()[$enumId]);
    }

    public function testEnumNotFoundInStubsFails(): void
    {
        $enumId   = '\\RoundingMode';
        $reflEnum = $this->makeEnum($enumId);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([]);

        $result = (new EnumConstantsValueCheck($provider))->run($stubs, $enumId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in stubs', $result->getFailures()[$enumId]);
    }

    // ── Matching values ───────────────────────────────────────────────────────

    public function testMatchingValuesPasses(): void
    {
        $enumId   = '\\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', 0)]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', 0)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsValueCheck($provider))->run($stubs, $enumId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Value mismatch ────────────────────────────────────────────────────────

    public function testValueMismatchFailsOnLatestPhp(): void
    {
        $enumId   = '\\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', 0)]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', 99)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsValueCheck($provider))->run($stubs, $enumId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($enumId . '::DefaultValue', $failures);
        $this->assertStringContainsString('value mismatch', $failures[$enumId . '::DefaultValue']);
        $this->assertStringNotContainsString('Class', $failures[$enumId . '::DefaultValue']);
    }

    public function testValueMismatchSkippedOnNonLatestPhp(): void
    {
        $enumId   = '\\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', 0)]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', 99)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsValueCheck($provider))->run($stubs, $enumId, PhpVersions::PHP_8_1->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Constant not in reflection is skipped ─────────────────────────────────

    public function testConstantNotInReflectionIsSkipped(): void
    {
        $enumId   = '\\RoundingMode';
        $reflEnum = $this->makeEnum($enumId); // no constants in reflection
        $stubEnum = $this->makeEnum($enumId, [$this->makeConstant('DefaultValue', 99)]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsValueCheck($provider))->run($stubs, $enumId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }
}
