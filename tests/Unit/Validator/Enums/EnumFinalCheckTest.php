<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumFinalCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class EnumFinalCheckTest extends CheckTestCase
{
    private function makeEnum(string $id, ?bool $isFinal = null): PHPEnum
    {
        $enum = new PHPEnum();
        $enum->setId($id);
        if ($isFinal !== null) {
            $enum->isFinal = $isFinal;
        }
        return $enum;
    }

    private function createMockReflectionProviderWithEnums(array $enums): ReflectionProviderInterface
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
        $check = new EnumFinalCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Matching final flag ───────────────────────────────────────────────────

    public function testFinalMatchPasses(): void
    {
        $enumId  = '\RoundingMode';
        $provider = $this->createMockReflectionProviderWithEnums([$this->makeEnum($enumId, true)]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$this->makeEnum($enumId, true)]);

        $result = (new EnumFinalCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testNonFinalMatchPasses(): void
    {
        $enumId  = '\RoundingMode';
        $provider = $this->createMockReflectionProviderWithEnums([$this->makeEnum($enumId, false)]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$this->makeEnum($enumId, false)]);

        $result = (new EnumFinalCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    public function testAbsentFinalPropertyTreatedAsFalse(): void
    {
        $enumId  = '\RoundingMode';
        $provider = $this->createMockReflectionProviderWithEnums([$this->makeEnum($enumId, false)]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$this->makeEnum($enumId)]); // isFinal not set

        $result = (new EnumFinalCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    // ── Mismatch ──────────────────────────────────────────────────────────────

    public function testFinalInReflectionButNotStubsFails(): void
    {
        $enumId  = '\RoundingMode';
        $provider = $this->createMockReflectionProviderWithEnums([$this->makeEnum($enumId, true)]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$this->makeEnum($enumId, false)]);

        $result = (new EnumFinalCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($enumId, $failures);
        $this->assertStringContainsString('final', $failures[$enumId]);
        $this->assertStringContainsString('non-final', $failures[$enumId]);
    }

    public function testNonFinalInReflectionButFinalInStubsFails(): void
    {
        $enumId  = '\RoundingMode';
        $provider = $this->createMockReflectionProviderWithEnums([$this->makeEnum($enumId, false)]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$this->makeEnum($enumId, true)]);

        $result = (new EnumFinalCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($enumId, $failures);
        $this->assertStringContainsString('Enum', $failures[$enumId]);
    }

    // ── Missing entity ────────────────────────────────────────────────────────

    public function testEnumNotFoundInReflectionFails(): void
    {
        $enumId  = '\MissingEnum';
        $provider = $this->createMockReflectionProviderWithEnums([]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$this->makeEnum($enumId, true)]);

        $result = (new EnumFinalCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($enumId, $failures);
        $this->assertStringContainsString('not found in reflection', $failures[$enumId]);
    }

    public function testEnumNotFoundInStubsFails(): void
    {
        $enumId  = '\MissingEnum';
        $provider = $this->createMockReflectionProviderWithEnums([$this->makeEnum($enumId, true)]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([]);

        $result = (new EnumFinalCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($enumId, $failures);
        $this->assertStringContainsString('not found in stubs', $failures[$enumId]);
    }
}
