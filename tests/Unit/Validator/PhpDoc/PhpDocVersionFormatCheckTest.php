<?php

namespace StubTests\Unit\Validator\PhpDoc;

use PHPUnit\Framework\Attributes\DataProvider;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Sources\Validator\PhpDoc\PhpDocVersionFormatCheck;

class PhpDocVersionFormatCheckTest extends CheckTestCase
{
    private PhpDocVersionFormatCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        KnownProblemsRegistry::reset();
        $this->check = new PhpDocVersionFormatCheck();
    }

    protected function tearDown(): void
    {
        KnownProblemsRegistry::reset();
        parent::tearDown();
    }

    // ── Build helpers ─────────────────────────────────────────────────────────

    private function makeClass(string $id, ?string $phpDoc = null, array $methods = []): PHPClass
    {
        $class = new PHPClass();
        $class->setId($id);
        $class->setName(ltrim($id, '\\'));
        if ($phpDoc !== null) {
            $class->setPhpDoc($phpDoc);
        }
        $class->methods = $methods;
        return $class;
    }

    private function makeMethod(string $name, ?string $phpDoc = null): PHPMethod
    {
        $method = new PHPMethod();
        $method->setName($name);
        if ($phpDoc !== null) {
            $method->setPhpDoc($phpDoc);
        }
        return $method;
    }

    private function makeFunction(string $id, ?string $phpDoc = null): PHPFunction
    {
        $function = new PHPFunction();
        $function->setId($id);
        $function->setName(ltrim($id, '\\'));
        if ($phpDoc !== null) {
            $function->setPhpDoc($phpDoc);
        }
        return $function;
    }

    private function makeStubsWithClass(PHPClass $class): \StubTests\Sources\Parsers\ParsedDataStorageManager
    {
        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([$class]);
        $stubs->method('getInterfaces')->willReturn([]);
        $stubs->method('getEnums')->willReturn([]);
        $stubs->method('getFunctions')->willReturn([]);
        return $stubs;
    }

    private function makeStubsWithFunction(PHPFunction $function): \StubTests\Sources\Parsers\ParsedDataStorageManager
    {
        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([]);
        $stubs->method('getInterfaces')->willReturn([]);
        $stubs->method('getEnums')->willReturn([]);
        $stubs->method('getFunctions')->willReturn([$function]);
        return $stubs;
    }

    private function runCheck(
        \StubTests\Sources\Parsers\ParsedDataStorageManager $stubs,
        string $entityId
    ): \StubTests\Sources\Validator\CheckResultSet {
        return $this->check->run($stubs, $entityId, PhpVersions::LATEST->value);
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsAllVersions(): void
    {
        $this->assertTrue($this->check->supports(PhpVersions::EARLIEST->value), 'PHP 5.6 must be supported');
        $this->assertTrue($this->check->supports(PhpVersions::PHP_7_0->value),  'PHP 7.0 must be supported');
        $this->assertTrue($this->check->supports(PhpVersions::PHP_8_0->value),  'PHP 8.0 must be supported');
        $this->assertTrue($this->check->supports(PhpVersions::LATEST->value),   'PHP 8.4 must be supported');
    }

    // ── Entity not found ──────────────────────────────────────────────────────

    public function testEntityNotFoundSucceeds(): void
    {
        $stubs = $this->createMockStorageManager();
        $stubs->method('getClasses')->willReturn([]);
        $stubs->method('getInterfaces')->willReturn([]);
        $stubs->method('getEnums')->willReturn([]);
        $stubs->method('getFunctions')->willReturn([]);

        $result = $this->runCheck($stubs, '\\NonExistent');

        $this->assertFalse($result->hasFailures(), 'Missing entity should not be a failure');
    }

    // ── No phpDoc / empty phpDoc ───────────────────────────────────────────────

    public function testEntityWithNoPhpDocSucceeds(): void
    {
        $class = $this->makeClass('\\TestClass', null);
        $result = $this->runCheck($this->makeStubsWithClass($class), '\\TestClass');
        $this->assertFalse($result->hasFailures());
    }

    public function testFunctionWithNoPhpDocSucceeds(): void
    {
        $function = $this->makeFunction('\\testFunc', null);
        $result = $this->runCheck($this->makeStubsWithFunction($function), '\\testFunc');
        $this->assertFalse($result->hasFailures());
    }

    // ── Valid (major.minor) version formats ───────────────────────────────────

    /**
     * @return array<string, array{string, string}>
     */
    public static function validVersionProvider(): array
    {
        return [
            '@since 5.6'      => ['since',      '5.6'],
            '@since 7.0'      => ['since',      '7.0'],
            '@since 8.0'      => ['since',      '8.0'],
            '@since 8.4'      => ['since',      '8.4'],
            '@since 0.4'      => ['since',      '0.4'],
            '@since 1.2'      => ['since',      '1.2'],
            '@deprecated 5.4' => ['deprecated', '5.4'],
            '@removed 8.0'    => ['removed',    '8.0'],
        ];
    }

    #[DataProvider('validVersionProvider')]
    public function testValidVersionSucceeds(string $tag, string $version): void
    {
        $phpDoc = "/**\n * @{$tag} {$version}\n */";
        $class = $this->makeClass('\\TestClass', $phpDoc);
        $result = $this->runCheck($this->makeStubsWithClass($class), '\\TestClass');
        $this->assertFalse($result->hasFailures(), "@{$tag} {$version} should be valid (major.minor only)");
    }

    // ── Patch-level versions (invalid) ────────────────────────────────────────

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function patchVersionProvider(): array
    {
        return [
            '@since 5.1.2'      => ['since',      '5.1.2',      '5.1'],
            '@since 7.0.7'      => ['since',      '7.0.7',      '7.0'],
            '@since 8.0.1'      => ['since',      '8.0.1',      '8.0'],
            '@since 5.6.1'      => ['since',      '5.6.1',      '5.6'],
            '@deprecated 5.1.3' => ['deprecated', '5.1.3',      '5.1'],
            '@removed 8.1.2'    => ['removed',    '8.1.2',      '8.1'],
            '@since 1.2.3'      => ['since',      '1.2.3',      '1.2'],
            '@since 4.4.5.35'   => ['since',      '4.4.5.35',   '4.4'],
        ];
    }

    #[DataProvider('patchVersionProvider')]
    public function testPatchVersionIsFailure(string $tag, string $version, string $expectedMajorMinor): void
    {
        $phpDoc = "/**\n * @{$tag} {$version}\n */";
        $class = $this->makeClass('\\TestClass', $phpDoc);
        $result = $this->runCheck($this->makeStubsWithClass($class), '\\TestClass');
        $this->assertTrue($result->hasFailures(), "@{$tag} {$version} should be flagged (use {$expectedMajorMinor} instead)");
        $this->assertStringContainsString($version, implode(' ', $result->getFailures()), "Failure message should mention the offending version");
    }

    // ── Non-numeric qualifiers are NOT flagged ────────────────────────────────

    public function testLibvirtStyleVersionNotFlagged(): void
    {
        // "0.4.1(-1)" has a non-numeric qualifier — should NOT be flagged
        $phpDoc = "/**\n * @since 0.4.1(-1)\n */";
        $class = $this->makeClass('\\TestClass', $phpDoc);
        $result = $this->runCheck($this->makeStubsWithClass($class), '\\TestClass');
        $this->assertFalse($result->hasFailures(), '0.4.1(-1) uses a non-numeric qualifier and should not be flagged');
    }

    public function testLibvirtStyleVersionWithDash2NotFlagged(): void
    {
        $phpDoc = "/**\n * @since 0.4.1(-2)\n */";
        $class = $this->makeClass('\\TestClass', $phpDoc);
        $result = $this->runCheck($this->makeStubsWithClass($class), '\\TestClass');
        $this->assertFalse($result->hasFailures(), '0.4.1(-2) uses a non-numeric qualifier and should not be flagged');
    }

    // ── Method phpDoc is also checked ─────────────────────────────────────────

    public function testPatchVersionInMethodPhpDocIsFailure(): void
    {
        $method = $this->makeMethod('doSomething', "/**\n * @since 7.0.7\n */");
        $class = $this->makeClass('\\TestClass', null, [$method]);
        $result = $this->runCheck($this->makeStubsWithClass($class), '\\TestClass');
        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey('\\TestClass::doSomething', $failures, 'Failure should identify the specific method');
    }

    public function testValidVersionInMethodPhpDocSucceeds(): void
    {
        $method = $this->makeMethod('doSomething', "/**\n * @since 7.0\n * @return void\n */");
        $class = $this->makeClass('\\TestClass', null, [$method]);
        $result = $this->runCheck($this->makeStubsWithClass($class), '\\TestClass');
        $this->assertFalse($result->hasFailures());
    }

    // ── Multiple patch versions in one phpDoc ─────────────────────────────────

    public function testMultiplePatchVersionsAllReported(): void
    {
        $phpDoc = "/**\n * @since 5.1.2\n * @deprecated 7.0.7\n */";
        $class = $this->makeClass('\\TestClass', $phpDoc);
        $result = $this->runCheck($this->makeStubsWithClass($class), '\\TestClass');
        $this->assertTrue($result->hasFailures());
        $message = implode(' ', $result->getFailures());
        $this->assertStringContainsString('5.1.2', $message);
        $this->assertStringContainsString('7.0.7', $message);
    }

    // ── Function entity ───────────────────────────────────────────────────────

    public function testFunctionWithValidVersionSucceeds(): void
    {
        $phpDoc = "/**\n * @since 5.6\n * @param string \$needle\n */";
        $function = $this->makeFunction('\\strpos', $phpDoc);
        $result = $this->runCheck($this->makeStubsWithFunction($function), '\\strpos');
        $this->assertFalse($result->hasFailures());
    }

    public function testFunctionWithPatchVersionIsFailure(): void
    {
        $phpDoc = "/**\n * @since 5.1.2\n * @param string \$needle\n */";
        $function = $this->makeFunction('\\strpos', $phpDoc);
        $result = $this->runCheck($this->makeStubsWithFunction($function), '\\strpos');
        $this->assertTrue($result->hasFailures());
    }

    // ── Known problems ────────────────────────────────────────────────────────

    public function testKnownProblemAtEntityLevelSkipsCheck(): void
    {
        KnownProblemsRegistry::reset();
        $provider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $provider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\TestClass',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PHPDOC_VERSION_FORMAT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Library uses its own versioning scheme with patch components'
            ),
        ]);
        KnownProblemsRegistry::getInstance($provider);
        $check = new PhpDocVersionFormatCheck();  // must be created AFTER the custom registry

        $phpDoc = "/**\n * @since 5.1.2\n */";
        $class = $this->makeClass('\\TestClass', $phpDoc);
        $stubs = $this->makeStubsWithClass($class);
        $result = $check->run($stubs, '\\TestClass', PhpVersions::LATEST->value);
        $this->assertFalse($result->hasFailures(), 'Known problem should suppress the failure');
    }
}
