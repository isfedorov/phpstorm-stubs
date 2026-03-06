<?php

namespace StubTests\Unit\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Model\Types\NullableType;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Classes\ClassMethodsUnionTypeForbiddenCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Unit\Validator\CheckTestCase;

class ClassMethodsUnionTypeForbiddenCheckTest extends CheckTestCase
{
    private ClassMethodsUnionTypeForbiddenCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        KnownProblemsRegistry::reset();
        $this->check = new ClassMethodsUnionTypeForbiddenCheck();
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
        bool $isTentative = false,
        array $parameters = []
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
        if (!empty($parameters)) {
            $method->setParameters($parameters);
        }
        return $method;
    }

    /**
     * Build a PHPParameter with an optional signature type, LanguageLevelTypeAware data,
     * and version bounds.
     */
    private function makeParam(
        string $name,
        mixed $type = null,
        ?array $languageLevelTypes = null,
        ?string $defaultType = null,
        ?string $sinceVersion = null,
        ?string $removedVersion = null
    ): PHPParameter {
        $param = new PHPParameter($name);
        if ($type !== null) {
            $param->setType($type);
        }
        if ($languageLevelTypes !== null) {
            $param->setLanguageLevelTypes($languageLevelTypes);
        }
        if ($defaultType !== null) {
            $param->setDefaultType($defaultType);
        }
        if ($sinceVersion !== null) {
            $param->setSinceVersion($sinceVersion);
        }
        if ($removedVersion !== null) {
            $param->setRemovedVersion($removedVersion);
        }
        return $param;
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsOnlyVersionsBeforePhp80(): void
    {
        $this->assertTrue($this->check->supports(PhpVersions::EARLIEST->value), 'PHP 5.6 must be supported');
        $this->assertTrue($this->check->supports(PhpVersions::PHP_7_0->value),  'PHP 7.0 must be supported');
        $this->assertTrue($this->check->supports(PhpVersions::PHP_7_4->value),  'PHP 7.4 must be supported');
        $this->assertFalse($this->check->supports(PhpVersions::PHP_8_0->value), 'PHP 8.0 must NOT be supported');
        $this->assertFalse($this->check->supports(PhpVersions::LATEST->value),  'PHP 8.4 must NOT be supported');
    }

    // ── Entity not found ──────────────────────────────────────────────────────

    public function testClassNotFoundInStubsSucceeds(): void
    {
        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([]);

        $result = $this->check->run($stubs, '\MissingClass', PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    // ── No methods / no union types ───────────────────────────────────────────

    public function testClassWithNoMethodsSucceeds(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties($className);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

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

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures());
    }

    public function testMethodWithNonUnionReturnTypeSucceeds(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getString', new StandaloneType('string'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Nullable type hint is NOT flagged ─────────────────────────────────────

    public function testNullableReturnTypeIsNotFlaggedAsUnionType(): void
    {
        // ?string serialises to 'string|null' via NullableType::toString(), but it is
        // valid from PHP 7.1 and must NOT be flagged by the union type check.
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getStr', $this->createNullableType('string'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures(), '?string return type must NOT be flagged by the union type check');
    }

    public function testNullableParamTypeIsNotFlaggedAsUnionType(): void
    {
        // ?string on a parameter serialises to 'string|null' but is valid from PHP 7.1.
        $className = '\MyClass';
        $method    = $this->makeMethod(
            'doSomething', null, null, null, 'public', false, false,
            [$this->makeParam('val', $this->createNullableType('string'))]
        );
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures(), '?string param type must NOT be flagged by the union type check');
    }

    // ── Union return type detection ───────────────────────────────────────────

    public function testUnionReturnTypeViaSignatureIsFailure(): void
    {
        // string|int in stub signature → UnionType → toString() = 'string|int' → flagged
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getValue', $this->createUnionType('string', 'int'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertTrue($result->hasFailures());
        $methodKey = $className . '::getValue';
        $this->assertArrayHasKey($methodKey, $result->getFailures());
        $this->assertStringContainsString('union return type', $result->getFailures()[$methodKey]);
        $this->assertStringContainsString('PHP 8.0', $result->getFailures()[$methodKey]);
    }

    public function testUnionReturnTypeViaSignatureIsFailureAlsoForPhp56(): void
    {
        // Check also triggers for PHP 5.6 — entities exclusive to PHP 5.6 must be covered.
        $className = '\LegacyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('get', $this->createUnionType('string', 'int'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::EARLIEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::get', $result->getFailures());
    }

    public function testUnionReturnTypeViaLLADefaultSucceeds(): void
    {
        // LanguageLevelTypeAware with default: 'string|int' → flagged
        $className = '\MyClass';
        $method    = new PHPMethod();
        $method->setName('getValue');
        $method->setAccess('public');
        $method->setLanguageLevelTypes([]);
        $method->setDefaultType('string|int');

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [$method]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures(), 'Union default type via LLA must not be flagged for PHP 7.4');
    }

    public function testUnionReturnTypeRestrictedToPhp80ViaLLASucceeds(): void
    {
        // #[LanguageLevelTypeAware(['8.0' => 'string|int'], default: 'string')] →
        // for PHP 7.4 resolves to 'string' → no union → OK
        $className = '\MyClass';
        $method    = new PHPMethod();
        $method->setName('getValue');
        $method->setAccess('public');
        $method->setLanguageLevelTypes(['8.0' => 'string|int']);
        $method->setDefaultType('string');

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [$method]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures(), 'Union type only from 8.0 via LLA must not be flagged for PHP 7.4');
    }

    // ── Version filtering: sinceVersion ───────────────────────────────────────

    public function testMethodAvailableOnlyFromPhp80IsNotCollectedForPhp74(): void
    {
        // sinceVersion = '8.0' → not included when collecting for PHP 7.4
        $className = '\MyClass';
        $method    = $this->makeMethod('getValue', $this->createUnionType('string', 'int'), '8.0');
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [$method]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures());
    }

    // ── Visibility: only overridable methods checked ──────────────────────────

    public function testPrivateMethodWithUnionTypeIsNotFlagged(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('privateGet', $this->createUnionType('string', 'int'), null, null, 'private')]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures(), 'Private method with union type must not be flagged');
    }

    public function testProtectedMethodWithUnionTypeIsFlagged(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('protectedGet', $this->createUnionType('string', 'int'), null, null, 'protected')]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertTrue($result->hasFailures(), 'Protected method with union type must be flagged');
    }

    public function testFinalMethodWithUnionTypeIsNotFlagged(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('finalGet', $this->createUnionType('string', 'int'), null, null, 'public', true)]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures(), 'Final method with union type must not be flagged');
    }

    public function testFinalClassMethodsAreNotFlagged(): void
    {
        $className = '\FinalClass';
        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getValue', $this->createUnionType('string', 'int'))]
        );
        $stubClass->isFinal = true;

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures(), 'Methods of a final class must not be flagged');
    }

    public function testMethodWithTentativeReturnTypeIsNotFlagged(): void
    {
        $className = '\MyClass';
        $method = $this->makeMethod('tentativeGet', $this->createUnionType('string', 'int'), null, null, 'public', false, true);

        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures(), 'Method with tentative return type must not be flagged');
    }

    // ── Union parameter type detection ────────────────────────────────────────

    public function testUnionParamTypeViaSignatureIsFailure(): void
    {
        $className = '\MyClass';
        $method    = $this->makeMethod(
            'doSomething', null, null, null, 'public', false, false,
            [$this->makeParam('val', $this->createUnionType('string', 'int'))]
        );
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertTrue($result->hasFailures());
        $paramKey = $className . '::doSomething::$val';
        $this->assertArrayHasKey($paramKey, $result->getFailures());
        $this->assertStringContainsString('union type hint', $result->getFailures()[$paramKey]);
        $this->assertStringContainsString('PHP 8.0', $result->getFailures()[$paramKey]);
    }

    public function testNonUnionParamTypeSucceeds(): void
    {
        $className = '\MyClass';
        $method    = $this->makeMethod(
            'doSomething', null, null, null, 'public', false, false,
            [$this->makeParam('val', new StandaloneType('string'))]
        );
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures());
    }

    public function testUnionParamTypeViaLLADefaultSucceeds(): void
    {
        // LanguageLevelTypeAware with default: 'string|int' → flagged
        $className = '\MyClass';
        $param     = $this->makeParam('val', null, [], 'string|int');
        $method    = $this->makeMethod('doSomething', null, null, null, 'public', false, false, [$param]);
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures(), 'Union default type via LLA must not be flagged for PHP 7.4');
    }

    public function testUnionParamTypeRestrictedToPhp80ViaLLASucceeds(): void
    {
        // #[LanguageLevelTypeAware(['8.0' => 'string|int'], default: 'string')] →
        // for PHP 7.4 resolves to 'string' → no union → OK
        $className = '\MyClass';
        $param     = $this->makeParam('val', null, ['8.0' => 'string|int'], 'string');
        $method    = $this->makeMethod('doSomething', null, null, null, 'public', false, false, [$param]);
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures(), 'Union type only from 8.0 via LLA must not be flagged for PHP 7.4');
    }

    public function testMethodWithUnionReturnAndUnionParamReportsBoth(): void
    {
        // Both return type and parameter type are union types → two separate failure entries.
        $className = '\MyClass';
        $method    = $this->makeMethod(
            'doSomething',
            $this->createUnionType('string', 'int'),    // union return
            null, null, 'public', false, false,
            [$this->makeParam('val', $this->createUnionType('bool', 'float'))]  // union param
        );
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($className . '::doSomething', $result->getFailures());
        $this->assertArrayHasKey($className . '::doSomething::$val', $result->getFailures());
        $this->assertEquals(2, $result->getFailureCount());
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
                affectedChecks: [CheckType::UNION_TYPE_FORBIDDEN],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Test class skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getValue', $this->createUnionType('string', 'int'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsUnionTypeForbiddenCheck(null, $registry))->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures());
        $this->assertStringContainsString('Test class skip', $result->getSuccesses()[0]);
    }

    public function testKnownProblemAtMethodLevelSkipsSpecificMethod(): void
    {
        $className      = '\MyClass';
        $methodEntityId = $className . '::getValue';

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: $methodEntityId,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::UNION_TYPE_FORBIDDEN],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Test method skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null,
            [$this->makeMethod('getValue', $this->createUnionType('string', 'int'))]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsUnionTypeForbiddenCheck(null, $registry))->run($stubs, $className, PhpVersions::PHP_7_4->value);

        $this->assertFalse($result->hasFailures());
    }
}
