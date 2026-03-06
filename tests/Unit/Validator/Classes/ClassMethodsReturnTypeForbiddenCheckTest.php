<?php

namespace StubTests\Unit\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Classes\ClassMethodsReturnTypeForbiddenCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Unit\Validator\CheckTestCase;

class ClassMethodsReturnTypeForbiddenCheckTest extends CheckTestCase
{
    private ClassMethodsReturnTypeForbiddenCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        KnownProblemsRegistry::reset();
        $this->check = new ClassMethodsReturnTypeForbiddenCheck();
    }

    protected function tearDown(): void
    {
        KnownProblemsRegistry::reset();
        parent::tearDown();
    }

    // ── Build helpers ─────────────────────────────────────────────────────────

    private function makeMethod(
        string $name,
        mixed $returnType = null,
        ?string $sinceVersion = null,
        ?string $removedVersion = null,
        string $access = 'public',
        bool $isFinal = false,
        bool $isTentative = false
    ): PHPMethod {
        $method = new PHPMethod();
        $method->setName($name);
        $method->setAccess($access);
        $method->setIsFinal($isFinal);
        $method->setHasTentativeReturnType($isTentative);
        if ($returnType !== null) {
            $method->setReturnTypeFromSignature($returnType);
        }
        if ($sinceVersion !== null) {
            $method->setSinceVersion($sinceVersion);
        }
        if ($removedVersion !== null) {
            $method->setRemovedVersion($removedVersion);
        }
        return $method;
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsOnlyVersionsBeforePhp70(): void
    {
        $this->assertTrue($this->check->supports(PhpVersions::EARLIEST->value), 'PHP 5.6 must be supported');
        $this->assertFalse($this->check->supports(PhpVersions::PHP_7_0->value), 'PHP 7.0 must NOT be supported');
        $this->assertFalse($this->check->supports(PhpVersions::PHP_7_1->value), 'PHP 7.1 must NOT be supported');
        $this->assertFalse($this->check->supports(PhpVersions::LATEST->value),  'PHP 8.4 must NOT be supported');
    }

    // ── Entity not found ──────────────────────────────────────────────────────

    public function testClassNotFoundInStubsSucceeds(): void
    {
        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([]);

        $result = $this->check->run($stubs, '\MissingClass', PhpVersions::EARLIEST->value);

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    // ── No methods / no return types ──────────────────────────────────────────

    public function testClassWithNoMethodsSucceeds(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties($className);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testMethodWithNoReturnTypeSucceeds(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('doSomething')]  // no return type
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Return type detection ─────────────────────────────────────────────────

    public function testReturnTypeViaSignatureIsFailure(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getString', new StandaloneType('string'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertTrue($result->hasFailures());
        $methodKey = $className . '::getString';
        $this->assertArrayHasKey($methodKey, $result->getFailures());
        $this->assertStringContainsString('return type', $result->getFailures()[$methodKey]);
        $this->assertStringContainsString('PHP 7.0', $result->getFailures()[$methodKey]);
    }

    public function testReturnTypeViaLLADefaultSucceeds(): void
    {
        // LanguageLevelTypeAware with default: 'string' → flagged
        $className = '\MyClass';
        $method    = new PHPMethod();
        $method->setName('getString');
        $method->setAccess('public');
        $method->setLanguageLevelTypes([]);
        $method->setDefaultType('string');

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [$method]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertFalse($result->hasFailures(), 'Return type via LLA must not be flagged for PHP 5.6');
    }

    public function testReturnTypeRestrictedToPhp70ViaLLASucceeds(): void
    {
        // #[LanguageLevelTypeAware(['7.0' => 'string'], default: '')] →
        // for PHP 5.6 resolves to '' → no return type → OK
        $className = '\MyClass';
        $method    = new PHPMethod();
        $method->setName('getString');
        $method->setAccess('public');
        $method->setLanguageLevelTypes(['7.0' => 'string']);
        $method->setDefaultType('');

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [$method]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertFalse($result->hasFailures(), 'Return type only from 7.0 via LLA must not be flagged for PHP 5.6');
    }

    // ── Version filtering: sinceVersion ───────────────────────────────────────

    public function testMethodAvailableOnlyFromPhp70IsNotCollectedForPhp56(): void
    {
        // sinceVersion = '7.0' → not included when collecting for PHP 5.6
        $className = '\MyClass';
        $method    = $this->makeMethod('getString', new StandaloneType('string'), '7.0');
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [$method]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Visibility: only overridable methods checked ──────────────────────────

    public function testPrivateMethodWithReturnTypeIsNotFlagged(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('privateGet', new StandaloneType('string'), null, null, 'private')]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertFalse($result->hasFailures(), 'Private method with return type must not be flagged');
    }

    public function testProtectedMethodWithReturnTypeIsFlagged(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('protectedGet', new StandaloneType('string'), null, null, 'protected')]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertTrue($result->hasFailures(), 'Protected method with return type must be flagged');
    }

    public function testFinalMethodWithReturnTypeIsNotFlagged(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('finalGet', new StandaloneType('string'), null, null, 'public', true)]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertFalse($result->hasFailures(), 'Final method with return type must not be flagged');
    }

    public function testFinalClassMethodsAreNotFlagged(): void
    {
        $className = '\FinalClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getString', new StandaloneType('string'))]
        );
        $stubClass->isFinal = true;

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertFalse($result->hasFailures(), 'Methods of a final class must not be flagged');
    }

    public function testMethodWithTentativeReturnTypeIsNotFlagged(): void
    {
        $className = '\MyClass';
        $method    = $this->makeMethod('tentativeGet', new StandaloneType('string'), null, null, 'public', false, true);
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertFalse($result->hasFailures(), 'Method with tentative return type must not be flagged');
    }

    // ── Known problems ────────────────────────────────────────────────────────

    public function testKnownProblemAtClassLevelSkipsAllMethods(): void
    {
        $className = '\SpecialClass';

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: $className,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::RETURN_TYPE_FORBIDDEN],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Test class skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry  = KnownProblemsRegistry::getInstance($knownProblemsProvider);
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getString', new StandaloneType('string'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsReturnTypeForbiddenCheck(null, $registry))->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertFalse($result->hasFailures());
        $this->assertStringContainsString('Test class skip', $result->getSuccesses()[0]);
    }

    public function testKnownProblemAtMethodLevelSkipsSpecificMethod(): void
    {
        $className      = '\MyClass';
        $methodEntityId = $className . '::getString';

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: $methodEntityId,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::RETURN_TYPE_FORBIDDEN],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Test method skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry  = KnownProblemsRegistry::getInstance($knownProblemsProvider);
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getString', new StandaloneType('string'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsReturnTypeForbiddenCheck(null, $registry))->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertFalse($result->hasFailures());
    }
}
