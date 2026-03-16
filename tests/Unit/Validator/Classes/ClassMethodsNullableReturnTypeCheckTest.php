<?php

namespace StubTests\Unit\Validator\Classes;

use StubTests\Framework\Parsers\Entities\Model\Access\PrivateAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\ProtectedAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\PublicAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\Types\NullableType;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Classes\ClassMethodsNullableReturnTypeCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Unit\Validator\CheckTestCase;

class ClassMethodsNullableReturnTypeCheckTest extends CheckTestCase
{
    private ClassMethodsNullableReturnTypeCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        KnownProblemsRegistry::reset();
        $this->check = new ClassMethodsNullableReturnTypeCheck();
    }

    protected function tearDown(): void
    {
        KnownProblemsRegistry::reset();
        parent::tearDown();
    }

    // ── Build helpers ─────────────────────────────────────────────────────────

    /**
     * Build a real PHPMethod with an optional return type, version bounds, visibility,
     * final flag, and tentative return type flag.
     */
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
        $method->setAccess(match ($access) {
            'protected' => new ProtectedAccessModifier(),
            'private'   => new PrivateAccessModifier(),
            default     => new PublicAccessModifier(),
        });
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

    /** Build a NullableType for the given base type (represents ?T in a stub signature). */
    private function makeNullableType(string $baseType): NullableType
    {
        return $this->createNullableType($baseType);
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsOnlyVersionsBeforePhp71(): void
    {
        $this->assertTrue($this->check->supports(PhpVersions::EARLIEST->value),  'PHP 5.6 must be supported');
        $this->assertTrue($this->check->supports(PhpVersions::PHP_7_0->value),   'PHP 7.0 must be supported');
        $this->assertFalse($this->check->supports(PhpVersions::PHP_7_1->value),  'PHP 7.1 must NOT be supported');
        $this->assertFalse($this->check->supports(PhpVersions::PHP_8_0->value),  'PHP 8.0 must NOT be supported');
        $this->assertFalse($this->check->supports(PhpVersions::LATEST->value),   'PHP 8.4 must NOT be supported');
    }

    // ── Entity not found ──────────────────────────────────────────────────────

    public function testClassNotFoundInStubsSucceeds(): void
    {
        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([]);

        $result = $this->check->run($stubs, '\MissingClass', PhpVersions::PHP_7_0->value);

        // Missing entity is not this check's responsibility — succeed silently.
        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    // ── No methods / no return type ───────────────────────────────────────────

    public function testClassWithNoMethodsSucceeds(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties($className);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_0->value);

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

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_0->value);

        $this->assertFalse($result->hasFailures());
    }

    public function testMethodWithNonNullableReturnTypeSucceeds(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getString', new StandaloneType('string'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_0->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Nullable return type detection ─────────────────────────────────────────

    public function testNullableReturnTypeViaSignatureIsFailure(): void
    {
        // ?string in stub signature → NullableType → toString() = 'string|null' → flagged
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getStr', $this->makeNullableType('string'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_0->value);

        $this->assertTrue($result->hasFailures());
        $methodKey = $className . '::getStr';
        $this->assertArrayHasKey($methodKey, $result->getFailures());
        $this->assertStringContainsString('nullable return type', $result->getFailures()[$methodKey]);
        $this->assertStringContainsString('PHP 7.1', $result->getFailures()[$methodKey]);
    }

    public function testNullableReturnTypeViaSignatureIsFailureAlsoForPhp56(): void
    {
        // Check also triggers for PHP 5.6 — entities exclusive to PHP 5.6 must be covered.
        $className = '\LegacyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('get', $this->makeNullableType('int'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::get', $result->getFailures());
    }

    public function testNullableReturnTypeViaLLADefaultIsFailure(): void
    {
        // LanguageLevelTypeAware with default: '?string' → getReturnTypeString returns '?string' → flagged
        $className  = '\MyClass';
        $method     = new PHPMethod();
        $method->setName('getStr');
        $method->setAccess(new PublicAccessModifier());
        $method->setLanguageLevelTypes([]);    // no version-specific entries
        $method->setDefaultType('?string');    // '?string' raw string as default

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [$method]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_0->value);

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::getStr', $result->getFailures());
    }

    public function testNullableReturnTypeRestrictedToPhp71ViaLLASucceeds(): void
    {
        // #[LanguageLevelTypeAware(['7.1' => '?string'], default: '')] → for PHP 7.0 returns '' → OK
        $className = '\MyClass';
        $method    = new PHPMethod();
        $method->setName('getStr');
        $method->setAccess(new PublicAccessModifier());
        $method->setLanguageLevelTypes(['7.1' => '?string']);
        $method->setDefaultType('');   // no type for pre-7.1

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [$method]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_0->value);

        $this->assertFalse($result->hasFailures(), '?string only from 7.1 via LLA must not be flagged for PHP 7.0');
    }

    // ── Version filtering: sinceVersion ───────────────────────────────────────

    public function testMethodAvailableOnlyFromPhp71IsNotCollectedForPhp70(): void
    {
        // sinceVersion = '7.1' → not included when collecting for PHP 7.0
        $className = '\MyClass';
        $method    = $this->makeMethod('getStr', $this->makeNullableType('string'), '7.1');
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [$method]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_0->value);

        // Method not available in PHP 7.0, so no failure
        $this->assertFalse($result->hasFailures());
    }

    // ── Visibility: only overridable methods checked ──────────────────────────

    public function testPrivateMethodWithNullableReturnTypeIsNotFlagged(): void
    {
        // Private methods cannot be overridden, so nullable types are irrelevant.
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('privateGet', $this->makeNullableType('string'), null, null, 'private')]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_0->value);

        $this->assertFalse($result->hasFailures(), 'Private method with nullable return must not be flagged');
    }

    public function testProtectedMethodWithNullableReturnTypeIsFlagged(): void
    {
        // Protected methods CAN be overridden — must be checked.
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('protectedGet', $this->makeNullableType('string'), null, null, 'protected')]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_0->value);

        $this->assertTrue($result->hasFailures(), 'Protected method with nullable return must be flagged');
    }

    public function testFinalMethodWithNullableReturnTypeIsNotFlagged(): void
    {
        // Final methods cannot be overridden, so nullable return types are irrelevant.
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('finalGet', $this->makeNullableType('string'), null, null, 'public', true)]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_0->value);

        $this->assertFalse($result->hasFailures(), 'Final method with nullable return must not be flagged');
    }

    public function testFinalClassMethodsAreNotFlagged(): void
    {
        // Final classes cannot be extended — no child class can ever override any method.
        $className = '\FinalClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getStr', $this->makeNullableType('string'))]
        );
        $stubClass->isFinal = true;

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_0->value);

        $this->assertFalse($result->hasFailures(), 'Methods of a final class must not be flagged');
    }

    public function testTentativeReturnTypeMethodIsNotFlagged(): void
    {
        // Methods with #[TentativeType] were introduced as non-enforced hints in PHP 8.1.
        // Subclasses can omit the return type without error, so there is no compatibility issue.
        $className = '\MyClass';
        $method = $this->makeMethod('tentativeGet', $this->makeNullableType('string'), null, null, 'public', false, true);

        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_0->value);

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
                affectedChecks: [CheckType::NULLABLE_RETURN_TYPE],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Test class skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getStr', $this->makeNullableType('string'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsNullableReturnTypeCheck(null, $registry))->run($stubs, $className, PhpVersions::PHP_7_0->value);

        $this->assertFalse($result->hasFailures());
        $this->assertStringContainsString('Test class skip', $result->getSuccesses()[0]);
    }

    public function testKnownProblemAtMethodLevelSkipsSpecificMethod(): void
    {
        $className      = '\MyClass';
        $methodEntityId = $className . '::getStr';

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: $methodEntityId,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::NULLABLE_RETURN_TYPE],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Test method skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getStr', $this->makeNullableType('string'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsNullableReturnTypeCheck(null, $registry))->run($stubs, $className, PhpVersions::PHP_7_0->value);

        $this->assertFalse($result->hasFailures());
    }
}
