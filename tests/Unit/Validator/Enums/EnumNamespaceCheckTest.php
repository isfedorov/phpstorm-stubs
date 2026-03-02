<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumNamespaceCheck;
use StubTests\Unit\Validator\CheckTestCase;

class EnumNamespaceCheckTest extends CheckTestCase
{
    private EnumNamespaceCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new EnumNamespaceCheck();
    }

    private function makeEnum(string $id, ?string $namespace = null): PHPEnum
    {
        $enum = new PHPEnum();
        $enum->setId($id);
        $enum->setName(ltrim($id, '\\'));
        if ($namespace !== null) {
            $enum->setNamespace($namespace);
        }
        return $enum;
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsAllPhpVersions(): void
    {
        $this->assertTrue($this->check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($this->check->supports(PhpVersions::LATEST->value));
    }

    // ── Root namespace enum ───────────────────────────────────────────────────

    public function testRootNamespaceEnumPassesWhenNamespaceIsBackslash(): void
    {
        $enum = $this->makeEnum('\RoundingMode', '\\');

        $stubs = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$enum]);

        $result = $this->check->run($stubs, '\RoundingMode', '8.1');

        $this->assertFalse($result->hasFailures());
    }

    // ── Namespaced enum ───────────────────────────────────────────────────────

    public function testNamespacedEnumPassesWhenNamespaceMatches(): void
    {
        $enum = $this->makeEnum('\Dom\AdjacentPosition', '\Dom');

        $stubs = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$enum]);

        $result = $this->check->run($stubs, '\Dom\AdjacentPosition', '8.4');

        $this->assertFalse($result->hasFailures());
    }

    public function testNamespacedEnumFailsWhenNamespaceMismatch(): void
    {
        $enum = $this->makeEnum('\Dom\AdjacentPosition', '\WrongNs');

        $stubs = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$enum]);

        $result = $this->check->run($stubs, '\Dom\AdjacentPosition', '8.4');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertStringContainsString('Namespace mismatch', $failures['\Dom\AdjacentPosition']);
        $this->assertStringContainsString('enum', $failures['\Dom\AdjacentPosition']);
    }

    // ── Enum not found ────────────────────────────────────────────────────────

    public function testEnumNotFoundInStubsIsFailure(): void
    {
        $stubs = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([]);

        $result = $this->check->run($stubs, '\MissingEnum', '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in stubs', $result->getFailures()['\MissingEnum']);
    }
}
