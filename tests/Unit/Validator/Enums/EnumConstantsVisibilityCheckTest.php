<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumConstantsVisibilityCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class EnumConstantsVisibilityCheckTest extends CheckTestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeConstant(string $name, string $visibility = 'public'): PHPClassConstant
    {
        $constant = new PHPClassConstant();
        $constant->setName($name);
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

    // ── Entity not found ──────────────────────────────────────────────────────

    public function testEnumNotFoundInReflectionFails(): void
    {
        $enumId  = '\\Status';
        $stubEnum = $this->makeEnum($enumId);

        $provider = $this->createMockReflectionProviderWithEnums([]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsVisibilityCheck($provider))->run($stubs, $enumId, PhpVersions::PHP_8_1->value);

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$enumId]);
        $this->assertStringContainsString('Enum', $result->getFailures()[$enumId]);
    }

    public function testEnumNotFoundInStubsFails(): void
    {
        $enumId   = '\\Status';
        $reflEnum = $this->makeEnum($enumId);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([]);

        $result = (new EnumConstantsVisibilityCheck($provider))->run($stubs, $enumId, PhpVersions::PHP_8_1->value);

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in stubs', $result->getFailures()[$enumId]);
    }

    // ── Matching visibility ───────────────────────────────────────────────────

    public function testMatchingVisibilityPasses(): void
    {
        $enumId   = '\\Status';
        $reflEnum = $this->makeEnum($enumId, [$this->makeConstant('DEFAULT', 'public')]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeConstant('DEFAULT', 'public')]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsVisibilityCheck($provider))->run($stubs, $enumId, PhpVersions::PHP_8_1->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Visibility mismatch ───────────────────────────────────────────────────

    public function testVisibilityMismatchFails(): void
    {
        $enumId   = '\\Status';
        $reflEnum = $this->makeEnum($enumId, [$this->makeConstant('DEFAULT', 'public')]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeConstant('DEFAULT', 'protected')]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsVisibilityCheck($provider))->run($stubs, $enumId, PhpVersions::PHP_8_1->value);

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($enumId . '::DEFAULT', $failures);
        $this->assertStringContainsString("'public'", $failures[$enumId . '::DEFAULT']);
        $this->assertStringContainsString("'protected'", $failures[$enumId . '::DEFAULT']);
    }

    // ── Constant not in reflection is skipped ─────────────────────────────────

    public function testConstantNotInReflectionIsSkipped(): void
    {
        $enumId   = '\\Status';
        $reflEnum = $this->makeEnum($enumId); // no constants in reflection
        $stubEnum = $this->makeEnum($enumId, [$this->makeConstant('EXTRA', 'protected')]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumConstantsVisibilityCheck($provider))->run($stubs, $enumId, PhpVersions::PHP_8_1->value);

        $this->assertFalse($result->hasFailures());
    }
}
