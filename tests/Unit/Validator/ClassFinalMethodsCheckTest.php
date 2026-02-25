<?php

namespace StubTests\Unit\Validator;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Validator\ClassFinalMethodsCheck;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Runner\PhpVersionRange;

class ClassFinalMethodsCheckTest extends CheckTestCase
{
    private ClassFinalMethodsCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        KnownProblemsRegistry::reset();
        $this->check = new ClassFinalMethodsCheck();
    }

    protected function tearDown(): void
    {
        KnownProblemsRegistry::reset();
        parent::tearDown();
    }

    /**
     * Build a PHPMethod with the given name, isFinal flag, and optional version bounds.
     */
    private function makeMethod(
        string $name,
        bool $isFinal = false,
        ?string $sinceVersion = null,
        ?string $removedVersion = null
    ): PHPMethod {
        $method = new PHPMethod();
        $method->setName($name);
        $method->setIsFinal($isFinal);
        if ($sinceVersion !== null) {
            $method->setSinceVersion($sinceVersion);
        }
        if ($removedVersion !== null) {
            $method->setRemovedVersion($removedVersion);
        }
        return $method;
    }

    /**
     * Build a real PHPInterface with the given id and optional methods.
     *
     * @param array<PHPMethod> $methods
     */
    private function makeInterface(string $id, array $methods = []): PHPInterface
    {
        $iface = new PHPInterface();
        $iface->setId($id);
        $iface->setName(ltrim($id, '\\'));
        $iface->methods = $methods;
        return $iface;
    }

    // ── Supports ──────────────────────────────────────────────────────────────

    public function testSupportsAllPhpVersions(): void
    {
        $this->assertTrue($this->check->supports('5.6'));
        $this->assertTrue($this->check->supports('7.0'));
        $this->assertTrue($this->check->supports('8.0'));
        $this->assertTrue($this->check->supports('8.4'));
    }

    // ── Basic matching ────────────────────────────────────────────────────────

    public function testClassWithNoMethodsSucceeds(): void
    {
        $className = '\MyClass';
        $reflectionClass = $this->createMockClassWithProperties($className);
        $stubClass       = $this->createMockClassWithProperties($className);

        $provider = $this->createMockReflectionProvider([], [$reflectionClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $check  = new ClassFinalMethodsCheck($provider);
        $result = $check->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testBothNonFinalIsSuccess(): void
    {
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', false)]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', false)]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testBothFinalIsSuccess(): void
    {
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', true)]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', true)]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Mismatch detection ────────────────────────────────────────────────────

    public function testReflectionFinalStubNonFinalIsFailure(): void
    {
        $className = '\MyClass';

        // reflection says final=true, stubs say final=false
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', true)]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', false)]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($className . '::doWork', $failures);
        $this->assertStringContainsString('final', $failures[$className . '::doWork']);
        $this->assertStringContainsString('non-final', $failures[$className . '::doWork']);
    }

    public function testReflectionNonFinalStubFinalIsFailure(): void
    {
        $className = '\MyClass';

        // reflection says final=false, stubs say final=true
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', false)]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', true)]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::doWork', $result->getFailures());
    }

    public function testMultipleMethodsMixedResultsAreAllReported(): void
    {
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [
                $this->makeMethod('okMethod',  false), // matches
                $this->makeMethod('badMethod', true),  // mismatch: refl=final, stub=non-final
            ]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [
                $this->makeMethod('okMethod',  false),
                $this->makeMethod('badMethod', false), // wrong
            ]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertCount(1, $result->getFailures());
        $this->assertArrayHasKey($className . '::badMethod', $result->getFailures());
    }

    // ── Missing stub method is not our concern ────────────────────────────────

    public function testMethodMissingInStubsIsNotReportedAsFinalMismatch(): void
    {
        // Existence is ClassMethodsExistCheck's job; here we only compare isFinal
        // for methods present in both sides.
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('onlyInReflection', true)]
        );
        $stubClass = $this->createMockClassWithProperties($className); // no methods

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

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

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$className]);
    }

    public function testClassNotFoundInStubsIsFailure(): void
    {
        $className    = '\MissingClass';
        $reflClass    = $this->createMockClassWithProperties($className);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in stubs', $result->getFailures()[$className]);
    }

    // ── Known problems ────────────────────────────────────────────────────────

    public function testClassLevelKnownProblemSkipsAllMethods(): void
    {
        $className = '\SpecialClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', true)] // mismatch: stub is non-final
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', false)]
        );

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: $className,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_FINAL_METHODS],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'Class-level skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassFinalMethodsCheck($provider, $registry))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
        $successes = $result->getSuccesses();
        $this->assertNotEmpty($successes);
        $this->assertStringContainsString('skipped', $successes[0]);
        $this->assertStringContainsString('Class-level skip', $successes[0]);
    }

    public function testMethodLevelKnownProblemSkipsSpecificMismatch(): void
    {
        $className       = '\MyClass';
        $mismatchedId    = $className . '::doWork';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [
                $this->makeMethod('okMethod', false), // matches
                $this->makeMethod('doWork',   true),  // mismatch — covered by known problem
            ]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [
                $this->makeMethod('okMethod', false),
                $this->makeMethod('doWork',   false), // wrong, but known problem
            ]
        );

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: $mismatchedId,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_FINAL_METHODS],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'Method-level skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassFinalMethodsCheck($provider, $registry))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
        $skipped = array_filter($result->getSuccesses(), fn($s) => str_contains($s, 'skipped'));
        $this->assertNotEmpty($skipped);
        $this->assertStringContainsString('Method-level skip', array_values($skipped)[0]);
    }

    // ── Version filtering ─────────────────────────────────────────────────────

    public function testStubMethodOutsideVersionRangeIsSkipped(): void
    {
        // Stub method has sinceVersion=8.1; checking PHP 8.0 → excluded from stub map
        // → treated as missing → no final mismatch reported
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('newMethod', true)]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('newMethod', false, '8.1')] // not available in 8.0
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── PS_UNRESERVE_PREFIX_ ──────────────────────────────────────────────────

    public function testPsUnreservePrefixMethodFinalMismatchIsReported(): void
    {
        // Generator::throw() in stubs is stored as PS_UNRESERVE_PREFIX_throw.
        // Reflection reports 'throw'. The check must strip the prefix before comparing.
        $className = '\Generator';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('throw', true)] // reflection: final
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('PS_UNRESERVE_PREFIX_throw', false)] // stubs: non-final → mismatch
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::throw', $result->getFailures());
    }

    public function testPsUnreservePrefixMethodFinalMatchIsSuccess(): void
    {
        $className = '\Generator';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('throw', true)] // reflection: final
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('PS_UNRESERVE_PREFIX_throw', true)] // stubs: also final → ok
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Hierarchy traversal ───────────────────────────────────────────────────

    public function testFinalMethodInheritedFromParentClassMismatchIsReported(): void
    {
        // ParentClass declares doWork as final.
        // ChildClass inherits it; reflection reports it as final for ChildClass too.
        // The stub defines doWork (on parent) as non-final → mismatch.
        $className       = '\ChildClass';
        $parentClassName = '\ParentClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', true)] // reflection sees final on child
        );

        $parentStub = new PHPClass();
        $parentStub->setId($parentClassName);
        $parentStub->methods = [$this->makeMethod('doWork', false)]; // stub: non-final → mismatch

        $childStub = $this->createMockClassWithProperties($className);
        $childStub->parentClass = $parentStub;

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$childStub]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::doWork', $result->getFailures());
    }

    public function testFinalMethodInheritedFromParentClassMatchIsSuccess(): void
    {
        $className       = '\ChildClass';
        $parentClassName = '\ParentClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', true)]
        );

        $parentStub = new PHPClass();
        $parentStub->setId($parentClassName);
        $parentStub->methods = [$this->makeMethod('doWork', true)]; // matches

        $childStub = $this->createMockClassWithProperties($className);
        $childStub->parentClass = $parentStub;

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$childStub]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testFinalMethodFromInterfaceMismatchIsReported(): void
    {
        // Some interfaces declare default implementations with final (rare, but testable).
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('interfaceMethod', true)]
        );

        $iface = $this->makeInterface('\MyInterface', [
            $this->makeMethod('interfaceMethod', false), // stub interface says non-final → mismatch
        ]);

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [],  // no own methods
            null,
            [$iface]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::interfaceMethod', $result->getFailures());
    }

    public function testChildMethodOverridesParentForFinalCheck(): void
    {
        // Child re-declares doWork as non-final; parent had it as final.
        // Reflection reports non-final for the child (since child overrides).
        // The child's stub definition (non-final) must win in the map.
        $className       = '\ChildClass';
        $parentClassName = '\ParentClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', false)] // child override: non-final
        );

        $parentStub = new PHPClass();
        $parentStub->setId($parentClassName);
        $parentStub->methods = [$this->makeMethod('doWork', true)]; // parent: final (should be overridden)

        $childStub = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', false)] // child re-declares: non-final
        );
        $childStub->parentClass = $parentStub;

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$childStub]);

        $result = (new ClassFinalMethodsCheck($provider))->run($stubs, $className, '8.0');

        // Child's non-final matches reflection's non-final → no failure
        $this->assertFalse($result->hasFailures());
    }
}
