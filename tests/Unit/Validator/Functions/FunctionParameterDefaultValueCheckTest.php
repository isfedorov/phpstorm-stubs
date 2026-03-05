<?php

namespace StubTests\Unit\Validator\Functions;

use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Functions\FunctionParameterDefaultValueCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Unit\Validator\CheckTestCase;

class FunctionParameterDefaultValueCheckTest extends CheckTestCase
{
    private FunctionParameterDefaultValueCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        KnownProblemsRegistry::reset();
        $this->check = new FunctionParameterDefaultValueCheck();
    }

    protected function tearDown(): void
    {
        KnownProblemsRegistry::reset();
        parent::tearDown();
    }

    private function makeParam(
        string $name,
        bool $hasDefault = false,
        mixed $defaultValue = null,
        ?string $since = null,
        ?string $removed = null
    ): PHPParameter {
        $param = new PHPParameter($name);
        $param->setHasDefaultValue($hasDefault);
        if ($hasDefault) {
            $param->setDefaultValue($defaultValue);
            $param->setIsOptional(true);
        }
        if ($since !== null) {
            $param->setSinceVersion($since);
        }
        if ($removed !== null) {
            $param->setRemovedVersion($removed);
        }
        return $param;
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsOnlyLatestPhpVersion(): void
    {
        $this->assertFalse($this->check->supports(PhpVersions::PHP_8_0->value));
        $this->assertFalse($this->check->supports(PhpVersions::PHP_8_3->value));
        $this->assertTrue($this->check->supports(PhpVersions::LATEST->value));
    }

    // ── Function not found ────────────────────────────────────────────────────

    public function testFunctionNotFoundInReflectionIsFailure(): void
    {
        $fnId       = '\\missingFunc';
        $stubFunc   = $this->createMockFunction($fnId);

        $provider = $this->createMockReflectionProvider([], []);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([$stubFunc]);

        $result = (new FunctionParameterDefaultValueCheck($provider))->run($stubs, $fnId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$fnId]);
    }

    public function testFunctionNotFoundInStubsSucceeds(): void
    {
        // FunctionExistsCheck handles missing stubs; we succeed silently
        $fnId      = '\\missingFunc';
        $reflFunc  = $this->createMockFunction($fnId);

        $provider = $this->createMockReflectionProvider([$reflFunc], []);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([]);

        $result = (new FunctionParameterDefaultValueCheck($provider))->run($stubs, $fnId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Matching defaults ─────────────────────────────────────────────────────

    public function testMatchingIntegerDefaultSucceeds(): void
    {
        $fnId       = '\\sort';
        $reflParams = [$this->makeParam('flags', true, 0)];
        $stubParams = [$this->makeParam('flags', true, 0)];

        $reflFunc = $this->createMockFunction($fnId, $reflParams);
        $stubFunc = $this->createMockFunction($fnId, $stubParams);

        $provider = $this->createMockReflectionProvider([$reflFunc], []);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([$stubFunc]);

        $result = (new FunctionParameterDefaultValueCheck($provider))->run($stubs, $fnId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    public function testMatchingStringDefaultSucceeds(): void
    {
        $fnId       = '\\implode';
        $reflParams = [$this->makeParam('separator', true, '')];
        $stubParams = [$this->makeParam('separator', true, '')];

        $reflFunc = $this->createMockFunction($fnId, $reflParams);
        $stubFunc = $this->createMockFunction($fnId, $stubParams);

        $provider = $this->createMockReflectionProvider([$reflFunc], []);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([$stubFunc]);

        $result = (new FunctionParameterDefaultValueCheck($provider))->run($stubs, $fnId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Mismatch detection ────────────────────────────────────────────────────

    public function testMismatchedDefaultIsFailure(): void
    {
        $fnId       = '\\sort';
        $reflParams = [$this->makeParam('flags', true, 0)];  // reflection: 0
        $stubParams = [$this->makeParam('flags', true, 4)];  // stub: 4 — wrong

        $reflFunc = $this->createMockFunction($fnId, $reflParams);
        $stubFunc = $this->createMockFunction($fnId, $stubParams);

        $provider = $this->createMockReflectionProvider([$reflFunc], []);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([$stubFunc]);

        $result = (new FunctionParameterDefaultValueCheck($provider))->run($stubs, $fnId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($fnId, $result->getFailures());
        $message = $result->getFailures()[$fnId];
        $this->assertStringContainsString('$flags', $message);
        $this->assertStringContainsString("reflection '0'", $message);
        $this->assertStringContainsString("stubs '4'", $message);
    }

    public function testMultipleMismatchesReportedTogether(): void
    {
        $fnId       = '\\func';
        $reflParams = [
            $this->makeParam('a', true, 1),
            $this->makeParam('b', true, 'x'),
        ];
        $stubParams = [
            $this->makeParam('a', true, 2),    // wrong
            $this->makeParam('b', true, 'y'),  // wrong
        ];

        $reflFunc = $this->createMockFunction($fnId, $reflParams);
        $stubFunc = $this->createMockFunction($fnId, $stubParams);

        $provider = $this->createMockReflectionProvider([$reflFunc], []);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([$stubFunc]);

        $result = (new FunctionParameterDefaultValueCheck($provider))->run($stubs, $fnId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $message = $result->getFailures()[$fnId];
        $this->assertStringContainsString('$a', $message);
        $this->assertStringContainsString('$b', $message);
    }

    // ── Null-skip behaviour ───────────────────────────────────────────────────

    public function testNullReflectionDefaultIsSkipped(): void
    {
        $fnId       = '\\func';
        $reflParams = [$this->makeParam('x', true, null)]; // null reflection default
        $stubParams = [$this->makeParam('x', true, 42)];   // stub has 42

        $reflFunc = $this->createMockFunction($fnId, $reflParams);
        $stubFunc = $this->createMockFunction($fnId, $stubParams);

        $provider = $this->createMockReflectionProvider([$reflFunc], []);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([$stubFunc]);

        $result = (new FunctionParameterDefaultValueCheck($provider))->run($stubs, $fnId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    public function testNullStubDefaultIsSkipped(): void
    {
        $fnId       = '\\func';
        $reflParams = [$this->makeParam('x', true, 42)];   // reflection: 42
        $stubParams = [$this->makeParam('x', true, null)]; // stub: null (unevaluable or actual null)

        $reflFunc = $this->createMockFunction($fnId, $reflParams);
        $stubFunc = $this->createMockFunction($fnId, $stubParams);

        $provider = $this->createMockReflectionProvider([$reflFunc], []);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([$stubFunc]);

        $result = (new FunctionParameterDefaultValueCheck($provider))->run($stubs, $fnId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── No default in reflection ──────────────────────────────────────────────

    public function testNoReflectionDefaultIsSkipped(): void
    {
        $fnId       = '\\func';
        $reflParams = [$this->makeParam('x', false)];    // no default in reflection
        $stubParams = [$this->makeParam('x', true, 42)]; // stub has one

        $reflFunc = $this->createMockFunction($fnId, $reflParams);
        $stubFunc = $this->createMockFunction($fnId, $stubParams);

        $provider = $this->createMockReflectionProvider([$reflFunc], []);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([$stubFunc]);

        $result = (new FunctionParameterDefaultValueCheck($provider))->run($stubs, $fnId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Known problem ─────────────────────────────────────────────────────────

    public function testKnownProblemSkipsFunction(): void
    {
        $fnId       = '\\problematic';
        $reflParams = [$this->makeParam('flags', true, 0)];
        $stubParams = [$this->makeParam('flags', true, 99)]; // mismatch

        $reflFunc = $this->createMockFunction($fnId, $reflParams);
        $stubFunc = $this->createMockFunction($fnId, $stubParams);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: $fnId,
                type: ProblemType::RUNTIME_VALUE,
                affectedChecks: [CheckType::PARAMETER_DEFAULT_VALUE],
                versionRange: new PhpVersionRange(PhpVersions::LATEST, PhpVersions::LATEST),
                reason: 'Platform-specific default value'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProvider([$reflFunc], []);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([$stubFunc]);

        $result = (new FunctionParameterDefaultValueCheck($provider, $registry))->run($stubs, $fnId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
        $skipped = array_filter($result->getSuccesses(), fn($s) => str_contains($s, 'skipped'));
        $this->assertNotEmpty($skipped);
        $this->assertStringContainsString('Platform-specific default value', array_values($skipped)[0]);
    }
}
