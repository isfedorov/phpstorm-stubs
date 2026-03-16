<?php

namespace StubTests\Unit\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Classes\ClassMethodsTentativeReturnTypeCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Unit\Validator\CheckTestCase;

class ClassMethodsTentativeReturnTypeCheckTest extends CheckTestCase
{
    private ClassMethodsTentativeReturnTypeCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        KnownProblemsRegistry::reset();
        $this->check = new ClassMethodsTentativeReturnTypeCheck();
    }

    protected function tearDown(): void
    {
        KnownProblemsRegistry::reset();
        parent::tearDown();
    }

    private function makeMethod(
        string $name,
        bool $tentative = false,
        ?string $sinceVersion = null,
        ?string $removedVersion = null
    ): PHPMethod {
        $method = new PHPMethod();
        $method->setName($name);
        $method->setHasTentativeReturnType($tentative);
        if ($sinceVersion !== null) {
            $method->setSinceVersion($sinceVersion);
        }
        if ($removedVersion !== null) {
            $method->setRemovedVersion($removedVersion);
        }
        return $method;
    }

    private function makeInterface(string $id, array $methods = []): PHPInterface
    {
        $iface = new PHPInterface();
        $iface->setId($id);
        $iface->setName(ltrim($id, '\\'));
        $iface->setMethods($methods);
        return $iface;
    }

    // ── Supports ──────────────────────────────────────────────────────────────

    public function testSupportsPhp81AndAbove(): void
    {
        $this->assertFalse($this->check->supports(PhpVersions::EARLIEST->value));
        $this->assertFalse($this->check->supports(PhpVersions::PHP_7_0->value));
        $this->assertFalse($this->check->supports(PhpVersions::PHP_8_0->value));
        $this->assertTrue($this->check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($this->check->supports(PhpVersions::PHP_8_2->value));
        $this->assertTrue($this->check->supports(PhpVersions::LATEST->value));
    }

    // ── Basic matching ────────────────────────────────────────────────────────

    public function testClassWithNoMethodsSucceeds(): void
    {
        $className       = '\MyClass';
        $reflectionClass = $this->createMockClassWithProperties($className);
        $stubClass       = $this->createMockClassWithProperties($className);

        $provider = $this->createMockReflectionProvider([], [$reflectionClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider))->run($stubs, $className, '8.1');

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testBothNonTentativeIsSuccess(): void
    {
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('doWork', false)]);
        $stubClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('doWork', false)]);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider))->run($stubs, $className, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    public function testBothTentativeIsSuccess(): void
    {
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('current', true)]);
        $stubClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('current', true)]);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider))->run($stubs, $className, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    // ── Mismatch detection ────────────────────────────────────────────────────

    public function testReflectionTentativeStubNotTentativeIsFailure(): void
    {
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('current', true)]);   // reflection: tentative
        $stubClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('current', false)]);  // stubs: not tentative

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider))->run($stubs, $className, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($className . '::current', $failures);
        $this->assertStringContainsString('tentative return type', $failures[$className . '::current']);
        $this->assertStringContainsString('#[TentativeType]', $failures[$className . '::current']);
    }

    public function testStubTentativeReflectionNotTentativeIsFailure(): void
    {
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('doWork', false)]);   // reflection: not tentative
        $stubClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('doWork', true)]);    // stubs: incorrectly marked tentative

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider))->run($stubs, $className, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($className . '::doWork', $failures);
        $this->assertStringContainsString('#[TentativeType]', $failures[$className . '::doWork']);
        $this->assertStringContainsString('does not have a tentative return type', $failures[$className . '::doWork']);
    }

    public function testMultipleMethodsMismatchesAreAllReported(): void
    {
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties($className, null, null, null, [
            $this->makeMethod('ok',      false), // matches
            $this->makeMethod('current', true),  // mismatch: refl=tentative, stub=not
        ]);
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [
            $this->makeMethod('ok',      false),
            $this->makeMethod('current', false), // wrong
        ]);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider))->run($stubs, $className, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertCount(1, $result->getFailures());
        $this->assertArrayHasKey($className . '::current', $result->getFailures());
    }

    // ── Missing stub method is not our concern ────────────────────────────────

    public function testMethodMissingInStubsIsNotReported(): void
    {
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('current', true)]);
        $stubClass = $this->createMockClassWithProperties($className); // no methods

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider))->run($stubs, $className, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    // ── Not found ─────────────────────────────────────────────────────────────

    public function testClassNotFoundInReflectionIsFailure(): void
    {
        $className = '\MissingClass';
        $stubClass = $this->createMockClassWithProperties($className);

        $provider = $this->createMockReflectionProvider([], []);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider))->run($stubs, $className, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$className]);
    }

    public function testClassNotFoundInStubsIsFailure(): void
    {
        $className = '\MissingClass';
        $reflClass = $this->createMockClassWithProperties($className);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider))->run($stubs, $className, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in stubs', $result->getFailures()[$className]);
    }

    // ── Hierarchy traversal ───────────────────────────────────────────────────

    public function testTentativeMethodFromInterfaceMismatchIsReported(): void
    {
        $className = '\SplFixedArray';

        $reflClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('current', true)]);  // reflection: tentative

        $iface = $this->makeInterface('\Iterator', [
            $this->makeMethod('current', false), // stub interface: not tentative
        ]);

        $stubClass = $this->createMockClassWithProperties($className, null, null, null,
            [], null, [$iface]);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider))->run($stubs, $className, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::current', $result->getFailures());
    }

    public function testTentativeMethodFromInterfaceMatchIsSuccess(): void
    {
        $className = '\SplFixedArray';

        $reflClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('current', true)]);

        $iface = $this->makeInterface('\Iterator', [
            $this->makeMethod('current', true),  // stub interface: tentative ✓
        ]);

        $stubClass = $this->createMockClassWithProperties($className, null, null, null,
            [], null, [$iface]);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider))->run($stubs, $className, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    // ── Known problems ────────────────────────────────────────────────────────

    public function testClassLevelKnownProblemSkipsAllMethods(): void
    {
        $className = '\SpecialClass';

        $reflClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('current', true)]);  // would fail
        $stubClass = $this->createMockClassWithProperties($className, null, null, null,
            [$this->makeMethod('current', false)]);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: $className,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::TENTATIVE_RETURN_TYPE],
                versionRange: new PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST),
                reason: 'Class-level skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider, $registry))
            ->run($stubs, $className, '8.1');

        $this->assertFalse($result->hasFailures());
        $this->assertStringContainsString('skipped', $result->getSuccesses()[0]);
    }

    public function testMethodLevelKnownProblemSkipsSpecificMethod(): void
    {
        $className    = '\MyClass';
        $mismatchedId = $className . '::current';

        $reflClass = $this->createMockClassWithProperties($className, null, null, null, [
            $this->makeMethod('ok',      false),
            $this->makeMethod('current', true),   // mismatch — covered by known problem
        ]);
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [
            $this->makeMethod('ok',      false),
            $this->makeMethod('current', false),  // wrong, but known problem
        ]);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: $mismatchedId,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::TENTATIVE_RETURN_TYPE],
                versionRange: new PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST),
                reason: 'Method-level skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsTentativeReturnTypeCheck($provider, $registry))
            ->run($stubs, $className, '8.1');

        $this->assertFalse($result->hasFailures());
        $skipped = array_filter($result->getSuccesses(), fn($s) => str_contains($s, 'skipped'));
        $this->assertNotEmpty($skipped);
        $this->assertStringContainsString('Method-level skip', array_values($skipped)[0]);
    }
}
