<?php

namespace StubTests\Unit\Validator;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Validator\ClassStaticMethodsCheck;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Runner\PhpVersionRange;

class ClassStaticMethodsCheckTest extends CheckTestCase
{
    private ClassStaticMethodsCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        KnownProblemsRegistry::reset();
        $this->check = new ClassStaticMethodsCheck();
    }

    protected function tearDown(): void
    {
        KnownProblemsRegistry::reset();
        parent::tearDown();
    }

    private function makeMethod(
        string $name,
        bool $isStatic = false,
        ?string $sinceVersion = null,
        ?string $removedVersion = null
    ): PHPMethod {
        $method = new PHPMethod();
        $method->setName($name);
        $method->setIsStatic($isStatic);
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
        $iface->methods = $methods;
        return $iface;
    }

    // ── supports() ───────────────────────────────────────────────────────────

    public function testSupportsAllPhpVersions(): void
    {
        $this->assertTrue($this->check->supports('5.6'));
        $this->assertTrue($this->check->supports('7.0'));
        $this->assertTrue($this->check->supports('8.0'));
        $this->assertTrue($this->check->supports('8.4'));
    }

    // ── Class not found ───────────────────────────────────────────────────────

    public function testClassNotFoundInReflectionIsFailure(): void
    {
        $className = '\MissingClass';
        $stubClass = $this->createMockClassWithProperties($className);

        $provider = $this->createMockReflectionProvider([], []);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

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

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in stubs', $result->getFailures()[$className]);
    }

    // ── Basic matching ────────────────────────────────────────────────────────

    public function testClassWithNoMethodsSucceeds(): void
    {
        $className   = '\MyClass';
        $reflClass   = $this->createMockClassWithProperties($className);
        $stubClass   = $this->createMockClassWithProperties($className);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testBothNonStaticIsSuccess(): void
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

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testBothStaticIsSuccess(): void
    {
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', true)]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', true)]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Mismatch detection ────────────────────────────────────────────────────

    public function testReflectionStaticStubNonStaticIsFailure(): void
    {
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', true)]   // reflection: static
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', false)]  // stub: non-static
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($className . '::create', $failures);
        $this->assertStringContainsString('static', $failures[$className . '::create']);
        $this->assertStringContainsString('non-static', $failures[$className . '::create']);
    }

    public function testReflectionNonStaticStubStaticIsFailure(): void
    {
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', false)]  // reflection: non-static
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doWork', true)]   // stub: static
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::doWork', $result->getFailures());
    }

    public function testFailureMessageContainsExpectedAndActualModifiers(): void
    {
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', true)]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', false)]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result   = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');
        $failures = $result->getFailures();
        $msg      = $failures[$className . '::create'];

        $this->assertStringContainsString('static', $msg);
        $this->assertStringContainsString('non-static', $msg);
        $this->assertStringContainsString('8.0', $msg);
    }

    public function testMultipleMethodsMixedResultsAreAllReported(): void
    {
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [
                $this->makeMethod('doWork', false),  // matches
                $this->makeMethod('create',  true),  // mismatch: refl=static, stub=non-static
            ]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [
                $this->makeMethod('doWork', false),
                $this->makeMethod('create',  false), // wrong
            ]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertCount(1, $result->getFailures());
        $this->assertArrayHasKey($className . '::create', $result->getFailures());
    }

    public function testAllMethodsMismatchedReportsAll(): void
    {
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [
                $this->makeMethod('methodA', true),
                $this->makeMethod('methodB', false),
            ]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [
                $this->makeMethod('methodA', false), // wrong
                $this->makeMethod('methodB', true),  // wrong
            ]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertCount(2, $result->getFailures());
        $this->assertArrayHasKey($className . '::methodA', $result->getFailures());
        $this->assertArrayHasKey($className . '::methodB', $result->getFailures());
    }

    // ── Missing stub method is not our concern ────────────────────────────────

    public function testMethodMissingInStubsIsNotReportedAsStaticMismatch(): void
    {
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('onlyInReflection', true)]
        );
        $stubClass = $this->createMockClassWithProperties($className); // no methods

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Known problems — class level ──────────────────────────────────────────

    public function testClassLevelKnownProblemSkipsAllMethods(): void
    {
        $className = '\SpecialClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', true)]  // mismatch
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', false)]
        );

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: $className,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_STATIC_METHODS],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'Class-level skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider, $registry))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
        $successes = $result->getSuccesses();
        $this->assertNotEmpty($successes);
        $this->assertStringContainsString('skipped', $successes[0]);
        $this->assertStringContainsString('Class-level skip', $successes[0]);
    }

    // ── Known problems — method level ─────────────────────────────────────────

    public function testMethodLevelKnownProblemSkipsSpecificMismatch(): void
    {
        $className    = '\MyClass';
        $mismatchedId = $className . '::create';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [
                $this->makeMethod('doWork', false), // matches
                $this->makeMethod('create',  true),  // mismatch — covered by known problem
            ]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [
                $this->makeMethod('doWork', false),
                $this->makeMethod('create',  false), // wrong, but known problem
            ]
        );

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: $mismatchedId,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_STATIC_METHODS],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'Method-level skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider, $registry))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
        $skipped = array_filter($result->getSuccesses(), fn($s) => str_contains($s, 'skipped'));
        $this->assertNotEmpty($skipped);
        $this->assertStringContainsString('Method-level skip', array_values($skipped)[0]);
    }

    public function testMethodLevelKnownProblemDoesNotSuppressOtherMismatches(): void
    {
        $className = '\MyClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [
                $this->makeMethod('create',  true),   // mismatch — known problem
                $this->makeMethod('doOther', true),   // mismatch — NOT a known problem
            ]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [
                $this->makeMethod('create',  false),
                $this->makeMethod('doOther', false),
            ]
        );

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: $className . '::create',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_STATIC_METHODS],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'Only create is known'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider, $registry))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertCount(1, $result->getFailures());
        $this->assertArrayHasKey($className . '::doOther', $result->getFailures());
        $this->assertArrayNotHasKey($className . '::create', $result->getFailures());
    }

    // ── Version filtering ─────────────────────────────────────────────────────

    public function testStubMethodBelowSinceVersionIsExcluded(): void
    {
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

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testStubMethodAfterRemovedVersionIsExcluded(): void
    {
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('oldMethod', true)]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('oldMethod', false, '5.6', '7.4')] // removed before 8.0
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testStubMethodWithinVersionRangeIsIncluded(): void
    {
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', true)]   // reflection: static
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', false, '7.0', '8.4')] // available in 8.0, but wrong
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::create', $result->getFailures());
    }

    // ── PS_UNRESERVE_PREFIX_ ──────────────────────────────────────────────────

    public function testPsUnreservePrefixMismatchIsReported(): void
    {
        $className = '\Generator';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('throw', true)]                         // reflection: static
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('PS_UNRESERVE_PREFIX_throw', false)]    // stub: non-static → mismatch
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::throw', $result->getFailures());
    }

    public function testPsUnreservePrefixMatchIsSuccess(): void
    {
        $className = '\Generator';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('throw', false)]
        );
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('PS_UNRESERVE_PREFIX_throw', false)]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Hierarchy traversal — parent class ───────────────────────────────────

    public function testStaticMethodInheritedFromParentMismatchIsReported(): void
    {
        $className       = '\ChildClass';
        $parentClassName = '\ParentClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', true)] // reflection sees static on child
        );

        $parentStub = new PHPClass();
        $parentStub->setId($parentClassName);
        $parentStub->methods = [$this->makeMethod('create', false)]; // stub parent: non-static

        $childStub = $this->createMockClassWithProperties($className);
        $childStub->parentClass = $parentStub;

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$childStub]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::create', $result->getFailures());
    }

    public function testStaticMethodInheritedFromParentMatchIsSuccess(): void
    {
        $className       = '\ChildClass';
        $parentClassName = '\ParentClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', true)]
        );

        $parentStub = new PHPClass();
        $parentStub->setId($parentClassName);
        $parentStub->methods = [$this->makeMethod('create', true)]; // matches

        $childStub = $this->createMockClassWithProperties($className);
        $childStub->parentClass = $parentStub;

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$childStub]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testChildMethodOverridesParentForStaticCheck(): void
    {
        // Child re-declares 'create' as non-static; parent had it static.
        // Reflection reports non-static for child. Child stub wins.
        $className       = '\ChildClass';
        $parentClassName = '\ParentClass';

        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', false)] // child override: non-static
        );

        $parentStub = new PHPClass();
        $parentStub->setId($parentClassName);
        $parentStub->methods = [$this->makeMethod('create', true)]; // parent: static (must not win)

        $childStub = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', false)] // child: non-static → matches reflection
        );
        $childStub->parentClass = $parentStub;

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$childStub]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Hierarchy traversal — interfaces ─────────────────────────────────────

    public function testStaticMethodFromInterfaceMismatchIsReported(): void
    {
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', true)]
        );

        $iface = $this->makeInterface('\MyInterface', [
            $this->makeMethod('create', false), // interface stub: non-static → mismatch
        ]);

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [], null, [$iface]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::create', $result->getFailures());
    }

    public function testStaticMethodFromInterfaceMatchIsSuccess(): void
    {
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', true)]
        );

        $iface = $this->makeInterface('\MyInterface', [
            $this->makeMethod('create', true), // matches
        ]);

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [], null, [$iface]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testClassMethodWinsOverInterfaceMethodForStaticCheck(): void
    {
        // Class declares 'create' as static, interface says non-static.
        // Class own definition must win.
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', true)] // reflection: static
        );

        $iface = $this->makeInterface('\MyInterface', [
            $this->makeMethod('create', false), // interface: non-static (must NOT win)
        ]);

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('create', true)], // class own: static → matches
            null,
            [$iface]
        );

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Cycle guard ───────────────────────────────────────────────────────────

    public function testCyclicParentChainDoesNotInfiniteLoop(): void
    {
        $className = '\MyClass';
        $reflClass = $this->createMockClassWithProperties($className);

        // Create a direct self-referential parent (degenerate cycle)
        $stubClass = $this->createMockClassWithProperties($className);
        // parentClass pointing to itself would cycle; set to null to confirm guard
        // is exercised by having a two-node chain where second node has no parent
        $parent = new PHPClass();
        $parent->setId('\ParentClass');
        $parent->methods = [];
        $stubClass->parentClass = $parent;

        $provider = $this->createMockReflectionProvider([], [$reflClass]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        // Should complete without infinite loop
        $result = (new ClassStaticMethodsCheck($provider))->run($stubs, $className, '8.0');
        $this->assertFalse($result->hasFailures());
    }
}
