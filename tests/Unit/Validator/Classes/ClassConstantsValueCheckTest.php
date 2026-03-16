<?php

namespace StubTests\Unit\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Classes\ClassConstantsValueCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class ClassConstantsValueCheckTest extends CheckTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        KnownProblemsRegistry::reset();
    }

    protected function tearDown(): void
    {
        KnownProblemsRegistry::reset();
        parent::tearDown();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeConstant(string $name, mixed $value = null): PHPClassConstant
    {
        $constant = new PHPClassConstant();
        $constant->setName($name);
        $constant->value = $value;
        return $constant;
    }

    private function makeClass(string $id, array $constants = []): PHPClass
    {
        $class = new PHPClass();
        $class->setId($id);
        $class->setConstants($constants);
        return $class;
    }

    private function createMockReflectionProviderWithClasses(array $classes = []): ReflectionProviderInterface
    {
        $provider = $this->createMock(ReflectionProviderInterface::class);
        $manager  = $this->createMockStorageManager();
        $manager->method('getClasses')->willReturn($classes);
        $provider->method('getReflection')->willReturn($manager);
        return $provider;
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsAllVersions(): void
    {
        $check = new ClassConstantsValueCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_5_6->value));
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Entity not found ──────────────────────────────────────────────────────

    public function testClassNotFoundInReflectionFails(): void
    {
        $classId   = '\\DateTime';
        $stubClass = $this->makeClass($classId);

        $provider = $this->createMockReflectionProviderWithClasses([]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsValueCheck($provider))->run($stubs, $classId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$classId]);
        $this->assertStringContainsString('Class', $result->getFailures()[$classId]);
    }

    public function testClassNotFoundInStubsFails(): void
    {
        $classId   = '\\DateTime';
        $reflClass = $this->makeClass($classId);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([]);

        $result = (new ClassConstantsValueCheck($provider))->run($stubs, $classId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in stubs', $result->getFailures()[$classId]);
    }

    // ── Matching values ───────────────────────────────────────────────────────

    public function testMatchingValuesPasses(): void
    {
        $classId   = '\\DateTime';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('ATOM', 'Y-m-d\\TH:i:sP')]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('ATOM', 'Y-m-d\\TH:i:sP')]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsValueCheck($provider))->run($stubs, $classId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    public function testNoConstantsPasses(): void
    {
        $classId   = '\\stdClass';
        $reflClass = $this->makeClass($classId);
        $stubClass = $this->makeClass($classId);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsValueCheck($provider))->run($stubs, $classId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Value mismatch ────────────────────────────────────────────────────────

    public function testValueMismatchFailsOnLatestPhp(): void
    {
        $classId   = '\\DateTime';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('VERSION', 42)]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('VERSION', 0)]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsValueCheck($provider))->run($stubs, $classId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($classId . '::VERSION', $failures);
        $this->assertStringContainsString('value mismatch', $failures[$classId . '::VERSION']);
        $this->assertStringContainsString("reflection='42'", $failures[$classId . '::VERSION']);
        $this->assertStringContainsString("stub='0'", $failures[$classId . '::VERSION']);
    }

    public function testValueMismatchSkippedOnNonLatestPhp(): void
    {
        // Value comparison is intentionally skipped on non-latest PHP versions
        $classId   = '\\DateTime';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('VERSION', 42)]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('VERSION', 0)]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsValueCheck($provider))->run($stubs, $classId, PhpVersions::PHP_8_0->value);

        $this->assertFalse($result->hasFailures());
    }

    public function testNullStubValueSkipsValueCheck(): void
    {
        // Stub has null value (complex expression) — value check is skipped
        $classId   = '\\DateTime';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('VERSION', 42)]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('VERSION', null)]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsValueCheck($provider))->run($stubs, $classId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    public function testNullReflectionValueSkipsValueCheck(): void
    {
        // Reflection has null value — value check is skipped
        $classId   = '\\DateTime';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('VERSION', null)]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('VERSION', 99)]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsValueCheck($provider))->run($stubs, $classId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Constant not in reflection is skipped ─────────────────────────────────

    public function testConstantNotInReflectionIsSkipped(): void
    {
        // Existence is ClassConstantsCheck's responsibility; value check silently skips
        $classId   = '\\DateTime';
        $reflClass = $this->makeClass($classId); // no constants in reflection
        $stubClass = $this->makeClass($classId, [$this->makeConstant('GHOST', 42)]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsValueCheck($provider))->run($stubs, $classId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Known problems ────────────────────────────────────────────────────────

    public function testClassLevelKnownProblemSkipsEntireCheck(): void
    {
        $classId   = '\\SpecialClass';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('FOO', 1)]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('FOO', 99)]);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: $classId,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_CONSTANTS_VALUE],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Value skip reason'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsValueCheck($provider, $registry))->run($stubs, $classId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
        $successes = $result->getSuccesses();
        $this->assertStringContainsString('skipped', $successes[0]);
        $this->assertStringContainsString('Value skip reason', $successes[0]);
    }

    public function testConstantLevelKnownProblemSkipsSpecificConstant(): void
    {
        $classId   = '\\SpecialClass';
        $reflClass = $this->makeClass($classId, [
            $this->makeConstant('STABLE', 10),
            $this->makeConstant('ICU_DEPENDENT', 1),
        ]);
        $stubClass = $this->makeClass($classId, [
            $this->makeConstant('STABLE', 10),
            $this->makeConstant('ICU_DEPENDENT', 99), // value differs → known problem
        ]);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::CLASS_CONSTANT,
                entityId: $classId . '::ICU_DEPENDENT',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_CONSTANTS_VALUE],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'ICU-version-dependent value'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsValueCheck($provider, $registry))->run($stubs, $classId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
        $foundSkip = false;
        foreach ($result->getSuccesses() as $success) {
            if (str_contains($success, 'ICU-version-dependent value')) {
                $foundSkip = true;
                break;
            }
        }
        $this->assertTrue($foundSkip, 'Expected a success entry explaining the constant skip');
    }
}
