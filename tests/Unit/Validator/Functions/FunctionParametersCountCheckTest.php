<?php

namespace StubTests\Unit\Validator\Functions;

use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Functions\FunctionParametersCountCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Unit\Validator\CheckTestCase;

class FunctionParametersCountCheckTest extends CheckTestCase
{
    private FunctionParametersCountCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        KnownProblemsRegistry::reset();
        $this->check = new FunctionParametersCountCheck();
    }

    protected function tearDown(): void
    {
        KnownProblemsRegistry::reset();
        parent::tearDown();
    }

    /**
     * Build a PHPFunction with the given id and optional parameters.
     */
    private function makeFunction(string $id, array $parameters = []): PHPFunction
    {
        $fn = new PHPFunction();
        $fn->setId($id);
        $fn->setName(ltrim($id, '\\'));
        $fn->setParameters($parameters);
        return $fn;
    }

    /**
     * Build a PHPParameter with optional PhpStormStubsElementAvailable version bounds.
     */
    private function makeParam(
        string $name,
        ?string $sinceVersion = null,
        ?string $removedVersion = null
    ): PHPParameter {
        $param = new PHPParameter($name);
        if ($sinceVersion !== null) {
            $param->setSinceVersion($sinceVersion);
        }
        if ($removedVersion !== null) {
            $param->setRemovedVersion($removedVersion);
        }
        return $param;
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsAllPhpVersions(): void
    {
        $this->assertTrue($this->check->supports(PhpVersions::EARLIEST->value));
        $this->assertTrue($this->check->supports(PhpVersions::PHP_7_0->value));
        $this->assertTrue($this->check->supports(PhpVersions::PHP_8_0->value));
        $this->assertTrue($this->check->supports(PhpVersions::LATEST->value));
    }

    // ── Function not found ────────────────────────────────────────────────────

    public function testFunctionNotFoundInReflectionIsFailure(): void
    {
        $id = '\\missing_func';

        $provider = $this->createMockReflectionProvider([]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([$this->makeFunction($id)]);

        $result = (new FunctionParametersCountCheck($provider))->run($stubs, $id, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$id]);
    }

    public function testFunctionNotFoundInStubsIsSuccess(): void
    {
        // Existence is FunctionExistsCheck's responsibility — silently skip
        $id = '\\missing_func';

        $provider = $this->createMockReflectionProvider([$this->makeFunction($id)]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([]);

        $result = (new FunctionParametersCountCheck($provider))->run($stubs, $id, '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    // ── Basic matching ────────────────────────────────────────────────────────

    public function testMatchingParameterCountIsSuccess(): void
    {
        $id     = '\\my_func';
        $params = [$this->makeParam('a'), $this->makeParam('b')];

        $provider = $this->createMockReflectionProvider([$this->makeFunction($id, $params)]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([$this->makeFunction($id, $params)]);

        $result = (new FunctionParametersCountCheck($provider))->run($stubs, $id, '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testFunctionWithNoParametersSucceeds(): void
    {
        $id = '\\no_params_func';

        $provider = $this->createMockReflectionProvider([$this->makeFunction($id)]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([$this->makeFunction($id)]);

        $result = (new FunctionParametersCountCheck($provider))->run($stubs, $id, '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    // ── Mismatch detection ────────────────────────────────────────────────────

    public function testParameterCountMismatchIsFailure(): void
    {
        $id = '\\my_func';

        $provider = $this->createMockReflectionProvider([
            $this->makeFunction($id, [$this->makeParam('a'), $this->makeParam('b')]),
        ]);
        $stubs = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([
            $this->makeFunction($id, [$this->makeParam('a')]), // one param fewer
        ]);

        $result = (new FunctionParametersCountCheck($provider))->run($stubs, $id, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($id, $result->getFailures());
        $this->assertStringContainsString('2', $result->getFailures()[$id]);
        $this->assertStringContainsString('1', $result->getFailures()[$id]);
    }

    // ── PhpStormStubsElementAvailable (sinceVersion / removedVersion) ─────────

    public function testParamNotYetAddedIsExcludedFromCount(): void
    {
        // Stub has an extra param with sinceVersion=8.1; we check PHP 8.0 → not counted
        $id = '\\my_func';

        $provider = $this->createMockReflectionProvider([
            $this->makeFunction($id, [$this->makeParam('a')]),
        ]);
        $stubs = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([
            $this->makeFunction($id, [
                $this->makeParam('a'),
                $this->makeParam('b', '8.1'), // not yet available in 8.0
            ]),
        ]);

        $result = (new FunctionParametersCountCheck($provider))->run($stubs, $id, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testParamAddedAtExactSinceVersionIsIncluded(): void
    {
        // sinceVersion=8.0, phpVersion=8.0 → included (>= boundary)
        $id = '\\my_func';

        $provider = $this->createMockReflectionProvider([
            $this->makeFunction($id, [$this->makeParam('a'), $this->makeParam('b')]),
        ]);
        $stubs = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([
            $this->makeFunction($id, [
                $this->makeParam('a'),
                $this->makeParam('b', '8.0'), // exactly 8.0 → included
            ]),
        ]);

        $result = (new FunctionParametersCountCheck($provider))->run($stubs, $id, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testParamAtExactRemovedVersionIsStillIncluded(): void
    {
        // removedVersion=7.1, phpVersion=7.1 → inclusive <=, so still counted
        $id = '\\legacy_func';

        $provider = $this->createMockReflectionProvider([
            $this->makeFunction($id, [$this->makeParam('a'), $this->makeParam('b')]),
        ]);
        $stubs = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([
            $this->makeFunction($id, [
                $this->makeParam('a'),
                $this->makeParam('b', null, '7.1'), // last available in 7.1
            ]),
        ]);

        $result = (new FunctionParametersCountCheck($provider))->run($stubs, $id, '7.1');

        $this->assertFalse($result->hasFailures(), 'removedVersion==phpVersion: param still included (inclusive <=)');
    }

    public function testParamAfterRemovedVersionIsExcluded(): void
    {
        // removedVersion=7.1, phpVersion=7.2 → 7.2 > 7.1 → excluded
        $id = '\\legacy_func';

        $provider = $this->createMockReflectionProvider([
            $this->makeFunction($id, [$this->makeParam('a')]),
        ]);
        $stubs = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([
            $this->makeFunction($id, [
                $this->makeParam('a'),
                $this->makeParam('b', null, '7.1'), // gone in 7.2+
            ]),
        ]);

        $result = (new FunctionParametersCountCheck($provider))->run($stubs, $id, '7.2');

        $this->assertFalse($result->hasFailures(), 'Param excluded after removedVersion');
    }

    // ── Same-name deduplication (placeholder + variadic) ─────────────────────

    public function testPlaceholderAndVariadicWithSameNameCountAsOne(): void
    {
        // Stub: f($x, $vals[to:'7.4'], ...$vals)
        // In PHP 7.4: placeholder+variadic both available → unique names {'x','vals'} = 2
        $id = '\\my_func';

        $provider = $this->createMockReflectionProvider([
            $this->makeFunction($id, [$this->makeParam('x'), $this->makeParam('vals')]),
        ]);
        $stubs = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([
            $this->makeFunction($id, [
                $this->makeParam('x'),
                $this->makeParam('vals', null, '7.4'), // placeholder: available until 7.4
                $this->makeParam('vals'),              // variadic: always available
            ]),
        ]);

        $result = (new FunctionParametersCountCheck($provider))->run($stubs, $id, '7.4');

        $this->assertFalse($result->hasFailures(), 'Placeholder+variadic with same name counted as one');
    }

    public function testVariadicAloneCountsNormallyWhenPlaceholderExcluded(): void
    {
        // In PHP 8.0: placeholder excluded (to:'7.4'), variadic 'vals' counts alone → 2 unique
        $id = '\\my_func';

        $provider = $this->createMockReflectionProvider([
            $this->makeFunction($id, [$this->makeParam('x'), $this->makeParam('vals')]),
        ]);
        $stubs = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([
            $this->makeFunction($id, [
                $this->makeParam('x'),
                $this->makeParam('vals', null, '7.4'), // excluded in PHP 8.0
                $this->makeParam('vals'),              // only this counts in PHP 8.0
            ]),
        ]);

        $result = (new FunctionParametersCountCheck($provider))->run($stubs, $id, '8.0');

        $this->assertFalse($result->hasFailures(), 'Variadic alone (placeholder excluded) counts normally');
    }

    public function testVersionWindowParamCountedOnlyWithinRange(): void
    {
        // Param available from=7.0 to=7.1:
        //   PHP 7.0 → included (stub=2, refl=2 → ok)
        //   PHP 7.1 → included (stub=2, refl=2 → ok)
        //   PHP 7.2 → excluded (stub=1, refl=1 → ok)
        $id        = '\\range_func';
        $stubParam = $this->makeParam('b', '7.0', '7.1');

        foreach (['7.0', '7.1', '7.2'] as $version) {
            $expected = $version === '7.2' ? 1 : 2;

            $provider = $this->createMockReflectionProvider([
                $this->makeFunction($id, array_fill(0, $expected, $this->makeParam('_'))),
            ]);
            $stubs = $this->createMockStorageManager();
            $stubs->method('getFunctions')->willReturn([
                $this->makeFunction($id, [$this->makeParam('a'), $stubParam]),
            ]);

            $result = (new FunctionParametersCountCheck($provider))->run($stubs, $id, $version);
            $this->assertFalse($result->hasFailures(), "PHP {$version} should match (expected {$expected} params)");
        }
    }

    // ── Known problems ────────────────────────────────────────────────────────

    public function testKnownProblemSkipsValidation(): void
    {
        $id = '\\special_func';

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: $id,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Test skip reason'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        // Mismatch: refl has 2 params, stubs has 1 — would normally fail
        $provider = $this->createMockReflectionProvider([
            $this->makeFunction($id, [$this->makeParam('a'), $this->makeParam('b')]),
        ]);
        $stubs = $this->createMockStorageManager();
        $stubs->method('getFunctions')->willReturn([
            $this->makeFunction($id, [$this->makeParam('a')]),
        ]);

        $result = (new FunctionParametersCountCheck($provider, $registry))->run($stubs, $id, '8.0');

        $this->assertFalse($result->hasFailures());
        $successes = $result->getSuccesses();
        $this->assertNotEmpty($successes);
        $this->assertStringContainsString('Test skip reason', $successes[0]);
    }
}
