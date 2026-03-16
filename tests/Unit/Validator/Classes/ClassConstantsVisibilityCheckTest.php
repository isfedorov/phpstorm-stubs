<?php

namespace StubTests\Unit\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Classes\ClassConstantsVisibilityCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class ClassConstantsVisibilityCheckTest extends CheckTestCase
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

    private function makeConstant(string $name, string $visibility = 'public', ?string $sinceVersion = null, ?string $removedVersion = null): PHPClassConstant
    {
        $constant = new PHPClassConstant();
        $constant->setName($name);
        $constant->visibility = $visibility;
        if ($sinceVersion !== null) {
            $constant->setSinceVersion($sinceVersion);
        }
        if ($removedVersion !== null) {
            $constant->setRemovedVersion($removedVersion);
        }
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
        $check = new ClassConstantsVisibilityCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_5_6->value));
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Entity not found ──────────────────────────────────────────────────────

    public function testClassNotFoundInReflectionFails(): void
    {
        $classId  = '\\DateTime';
        $stubClass = $this->makeClass($classId);

        $provider = $this->createMockReflectionProviderWithClasses([]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$classId]);
    }

    public function testClassNotFoundInStubsFails(): void
    {
        $classId   = '\\DateTime';
        $reflClass = $this->makeClass($classId);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in stubs', $result->getFailures()[$classId]);
    }

    // ── Matching visibility ───────────────────────────────────────────────────

    public function testNoConstantsPasses(): void
    {
        $classId   = '\\stdClass';
        $reflClass = $this->makeClass($classId);
        $stubClass = $this->makeClass($classId);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testMatchingPublicVisibilityPasses(): void
    {
        $classId   = '\\DateTime';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('ATOM', 'public')]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('ATOM', 'public')]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testMatchingProtectedVisibilityPasses(): void
    {
        $classId   = '\\MyClass';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('SECRET', 'protected')]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('SECRET', 'protected')]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testMatchingPrivateVisibilityPasses(): void
    {
        $classId   = '\\MyClass';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('INTERNAL', 'private')]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('INTERNAL', 'private')]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Visibility mismatch ───────────────────────────────────────────────────

    public function testPublicInReflectionProtectedInStubFails(): void
    {
        $classId   = '\\DateTime';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('ATOM', 'public')]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('ATOM', 'protected')]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($classId . '::ATOM', $failures);
        $this->assertStringContainsString("'public'", $failures[$classId . '::ATOM']);
        $this->assertStringContainsString("'protected'", $failures[$classId . '::ATOM']);
    }

    public function testProtectedInReflectionPublicInStubFails(): void
    {
        $classId   = '\\MyClass';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('SECRET', 'protected')]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('SECRET', 'public')]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($classId . '::SECRET', $result->getFailures());
    }

    public function testPublicInReflectionPrivateInStubFails(): void
    {
        $classId   = '\\MyClass';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('VALUE', 'public')]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('VALUE', 'private')]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($classId . '::VALUE', $result->getFailures());
    }

    public function testMultipleConstantsMixedResultsReportedSeparately(): void
    {
        $classId   = '\\MyClass';
        $reflClass = $this->makeClass($classId, [
            $this->makeConstant('OK',  'public'),
            $this->makeConstant('BAD', 'public'),
        ]);
        $stubClass = $this->makeClass($classId, [
            $this->makeConstant('OK',  'public'),     // matches
            $this->makeConstant('BAD', 'protected'),  // mismatch
        ]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertCount(1, $result->getFailures());
        $this->assertArrayHasKey($classId . '::BAD', $result->getFailures());
        $this->assertArrayNotHasKey($classId . '::OK', $result->getFailures());
    }

    // ── Constant not in reflection is skipped ─────────────────────────────────

    public function testConstantNotInReflectionIsSkipped(): void
    {
        // Stub declares a constant absent from reflection — ClassConstantsCheck handles this.
        $classId   = '\\DateTime';
        $reflClass = $this->makeClass($classId); // no constants in reflection
        $stubClass = $this->makeClass($classId, [$this->makeConstant('GHOST', 'protected')]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Version filtering ─────────────────────────────────────────────────────

    public function testVersionFilteredConstantIsSkipped(): void
    {
        $classId   = '\\MyClass';
        // Stub constant only available from 8.1 — checking at 8.0 should skip it
        $reflClass = $this->makeClass($classId, [$this->makeConstant('NEW_CONST', 'public')]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('NEW_CONST', 'protected', '8.1')]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testVersionFilteredRemovedConstantIsSkipped(): void
    {
        $classId   = '\\MyClass';
        // Stub constant removed before current version — should be skipped
        $reflClass = $this->makeClass($classId, [$this->makeConstant('OLD_CONST', 'public')]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('OLD_CONST', 'protected', null, '8.0')]);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider))->run($stubs, $classId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Known problems ────────────────────────────────────────────────────────

    public function testClassLevelKnownProblemSkipsEntireCheck(): void
    {
        $classId   = '\\SpecialClass';
        $reflClass = $this->makeClass($classId, [$this->makeConstant('FLAG', 'public')]);
        $stubClass = $this->makeClass($classId, [$this->makeConstant('FLAG', 'protected')]);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: $classId,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_CONSTANTS_VISIBILITY],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Class-level visibility skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider, $registry))->run($stubs, $classId, '8.0');

        $this->assertFalse($result->hasFailures());
        $successes = $result->getSuccesses();
        $this->assertStringContainsString('skipped', $successes[0]);
        $this->assertStringContainsString('Class-level visibility skip', $successes[0]);
    }

    public function testConstantLevelKnownProblemSkipsSpecificConstant(): void
    {
        $classId   = '\\SpecialClass';
        $reflClass = $this->makeClass($classId, [
            $this->makeConstant('GOOD', 'public'),
            $this->makeConstant('LEGACY', 'public'),
        ]);
        $stubClass = $this->makeClass($classId, [
            $this->makeConstant('GOOD',   'public'),     // matches → passes
            $this->makeConstant('LEGACY', 'protected'),  // mismatch, but has known problem
        ]);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::CLASS_CONSTANT,
                entityId: $classId . '::LEGACY',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_CONSTANTS_VISIBILITY],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Constant-level visibility skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProviderWithClasses([$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassConstantsVisibilityCheck($provider, $registry))->run($stubs, $classId, '8.0');

        $this->assertFalse($result->hasFailures());
        $foundSkip = false;
        foreach ($result->getSuccesses() as $success) {
            if (str_contains($success, 'Constant-level visibility skip')) {
                $foundSkip = true;
                break;
            }
        }
        $this->assertTrue($foundSkip, 'Expected a success entry explaining the constant skip');
    }
}
