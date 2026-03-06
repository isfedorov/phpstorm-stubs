<?php

namespace StubTests\Unit\Validator\Classes;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Classes\ClassMethodsPhpDocConformsSignatureCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Unit\Validator\CheckTestCase;

class ClassMethodsPhpDocConformsSignatureCheckTest extends CheckTestCase
{
    private ClassMethodsPhpDocConformsSignatureCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        KnownProblemsRegistry::reset();
        $this->check = new ClassMethodsPhpDocConformsSignatureCheck();
    }

    protected function tearDown(): void
    {
        KnownProblemsRegistry::reset();
        parent::tearDown();
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsAllVersions(): void
    {
        $this->assertTrue($this->check->supports(PhpVersions::EARLIEST->value));
        $this->assertTrue($this->check->supports(PhpVersions::PHP_7_0->value));
        $this->assertTrue($this->check->supports(PhpVersions::LATEST->value));
    }

    // ── Class absent from stubs → silent success ──────────────────────────────

    public function testClassAbsentFromStubsSucceedsSilently(): void
    {
        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([]);

        $result = $this->check->run($stubs, '\Missing', '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    // ── No methods/properties → succeed ──────────────────────────────────────

    public function testClassWithNoMethodsSucceeds(): void
    {
        $className = '\MyClass';
        $stubClass = $this->createMockClassWithProperties($className);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    // ── Matching method return types pass ─────────────────────────────────────

    public function testMatchingReturnTypePasses(): void
    {
        $className = '\MyClass';
        $method = $this->makeMethodWithPhpDoc('doWork', 'array', 'array');
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Incompatible method return type fails ─────────────────────────────────

    public function testIncompatibleReturnTypeFails(): void
    {
        $className = '\MyClass';
        $method = $this->makeMethodWithPhpDoc('getCount', 'int', 'string');  // mismatch
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $key = $className . '::getCount';
        $this->assertArrayHasKey($key, $result->getFailures());
        $this->assertStringContainsString("sig 'int'", $result->getFailures()[$key]);
        $this->assertStringContainsString("phpdoc 'string'", $result->getFailures()[$key]);
    }

    // ── Param type mismatch fails ─────────────────────────────────────────────

    public function testParamTypeMismatchFails(): void
    {
        $className = '\MyClass';
        $param = new PHPParameter('n');
        $param->setType($this->createType('int'));
        $param->setTypeFromPhpDoc('string');  // mismatch

        $method = $this->makeMethodWithPhpDoc('process', null, null, [$param]);
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $key = $className . '::process';
        $this->assertArrayHasKey($key, $result->getFailures());
        $this->assertStringContainsString('$n', $result->getFailures()[$key]);
    }

    // ── No PhpDoc → skip (both return type and params) ────────────────────────

    public function testNoPhpDocReturnTypeIsSkipped(): void
    {
        $className = '\MyClass';
        $method = $this->makeMethodWithPhpDoc('getCount', 'int', null);  // no PhpDoc
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Property type mismatch fails ──────────────────────────────────────────

    public function testPropertyTypeMismatchFails(): void
    {
        $className = '\MyClass';
        $property = $this->makePropertyWithPhpDoc('name', 'int', 'string');  // mismatch

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [], null, [], [$property]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, '8.0');

        $this->assertTrue($result->hasFailures());
        $key = $className . '::$name';
        $this->assertArrayHasKey($key, $result->getFailures());
        $this->assertStringContainsString("sig 'int'", $result->getFailures()[$key]);
        $this->assertStringContainsString("phpdoc 'string'", $result->getFailures()[$key]);
    }

    public function testPropertyTypeMatchPasses(): void
    {
        $className = '\MyClass';
        $property = $this->makePropertyWithPhpDoc('count', 'int', 'int');

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [], null, [], [$property]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testPropertyNoPhpDocIsSkipped(): void
    {
        $className = '\MyClass';
        $property = $this->makePropertyWithPhpDoc('name', 'string', null);  // no PhpDoc

        $stubClass = $this->createMockClassWithProperties(
            $className, null, null, null, [], null, [], [$property]
        );

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Typed-array narrowing passes ──────────────────────────────────────────

    public function testTypedArrayNarrowingPasses(): void
    {
        $className = '\MyClass';
        $method = $this->makeMethodWithPhpDoc('getItems', 'array', 'string[]');
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── phpstan generics pass ─────────────────────────────────────────────────

    public function testPhpStanGenericsPass(): void
    {
        $className = '\MyClass';
        $method = $this->makeMethodWithPhpDoc('getMap', 'array', 'array<string, int>');
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── bool/false split passes ───────────────────────────────────────────────

    public function testBoolFalseNarrowingPasses(): void
    {
        $className = '\MyClass';
        $method = $this->makeMethodWithPhpDoc('check', 'bool', 'false');
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = $this->check->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Known problem at class level skips all checks ─────────────────────────

    public function testClassLevelKnownProblemSkips(): void
    {
        $className = '\SpecialClass';
        $method = $this->makeMethodWithPhpDoc('doWork', 'string', 'int');  // mismatch
        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$method]);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: $className,
                type: ProblemType::RUNTIME_VALUE,
                affectedChecks: [CheckType::PHPDOC_CONFORMS_SIGNATURE],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Class-level PhpDoc skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsPhpDocConformsSignatureCheck(null, $registry))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
        $skipped = array_filter($result->getSuccesses(), fn($s) => str_contains($s, 'skipped'));
        $this->assertNotEmpty($skipped);
        $this->assertStringContainsString('Class-level PhpDoc skip', array_values($skipped)[0]);
    }

    // ── Known problem at method level skips specific method ───────────────────

    public function testMethodLevelKnownProblemSkipsSpecificMismatch(): void
    {
        $className    = '\MyClass';
        $mismatchedId = $className . '::badMethod';

        $badMethod  = $this->makeMethodWithPhpDoc('badMethod', 'string', 'int');  // mismatch
        $goodMethod = $this->makeMethodWithPhpDoc('goodMethod', 'string', 'string');  // ok

        $stubClass = $this->createMockClassWithProperties($className, null, null, null, [$badMethod, $goodMethod]);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: $mismatchedId,
                type: ProblemType::RUNTIME_VALUE,
                affectedChecks: [CheckType::PHPDOC_CONFORMS_SIGNATURE],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Method-level PhpDoc skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$stubClass]);

        $result = (new ClassMethodsPhpDocConformsSignatureCheck(null, $registry))->run($stubs, $className, '8.0');

        $this->assertFalse($result->hasFailures());
        $skipped = array_filter($result->getSuccesses(), fn($s) => str_contains($s, 'skipped'));
        $this->assertNotEmpty($skipped);
        $this->assertStringContainsString('Method-level PhpDoc skip', array_values($skipped)[0]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Create a PHPMethod mock with configurable return types and parameters.
     *
     * @param PHPParameter[] $params
     */
    private function makeMethodWithPhpDoc(
        string $name,
        ?string $sigReturnType,
        ?string $docReturnType,
        array $params = []
    ): PHPMethod {
        $method = $this->getMockBuilder(PHPMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'getParameters', 'getReturnTypeFromSignature', 'getReturnTypeFromPhpDoc', 'getSinceVersion', 'getRemovedVersion'])
            ->getMock();

        $method->method('getName')->willReturn($name);
        $method->method('getParameters')->willReturn($params);
        $method->method('getSinceVersion')->willReturn(null);
        $method->method('getRemovedVersion')->willReturn(null);

        if ($sigReturnType !== null) {
            $method->method('getReturnTypeFromSignature')->willReturn($this->createType($sigReturnType));
        } else {
            $method->method('getReturnTypeFromSignature')->willReturn(null);
        }

        $method->method('getReturnTypeFromPhpDoc')->willReturn($docReturnType);

        return $method;
    }

    /**
     * Create a PHPProperty mock with configurable signature and PhpDoc types.
     */
    private function makePropertyWithPhpDoc(
        string $name,
        ?string $sigType,
        ?string $docType
    ): PHPProperty {
        $property = $this->getMockBuilder(PHPProperty::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'getType', 'getTypeFromPhpDoc', 'getSinceVersion', 'getRemovedVersion'])
            ->getMock();

        $property->method('getName')->willReturn($name);
        $property->method('getSinceVersion')->willReturn(null);
        $property->method('getRemovedVersion')->willReturn(null);

        if ($sigType !== null) {
            $property->method('getType')->willReturn($this->createType($sigType));
        } else {
            $property->method('getType')->willReturn(null);
        }

        $property->method('getTypeFromPhpDoc')->willReturn($docType);

        return $property;
    }
}
