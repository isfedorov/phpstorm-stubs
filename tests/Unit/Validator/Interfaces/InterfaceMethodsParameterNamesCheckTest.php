<?php

namespace StubTests\Unit\Validator\Interfaces;

use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsParameterNamesCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class InterfaceMethodsParameterNamesCheckTest extends CheckTestCase
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

    private function makeParam(string $name): PHPParameter
    {
        return new PHPParameter($name);
    }

    private function makeMethod(string $name, array $parameters = []): PHPMethod
    {
        $method = new PHPMethod();
        $method->setName($name);
        $method->setParameters($parameters);
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

    private function createMockReflectionProviderWithInterfaces(array $interfaces = []): ReflectionProviderInterface
    {
        $provider = $this->createMock(ReflectionProviderInterface::class);
        $manager  = $this->createMockStorageManager();
        $manager->method('getInterfaces')->willReturn($interfaces);
        $provider->method('getReflection')->willReturn($manager);
        return $provider;
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsPhp80AndAbove(): void
    {
        $check = new InterfaceMethodsParameterNamesCheck();
        $this->assertFalse($check->supports(PhpVersions::EARLIEST->value));
        $this->assertFalse($check->supports(PhpVersions::PHP_5_6->value));
        $this->assertFalse($check->supports(PhpVersions::PHP_7_4->value));
        $this->assertTrue($check->supports(PhpVersions::PHP_8_0->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Not found ─────────────────────────────────────────────────────────────

    public function testInterfaceNotFoundInReflectionIsFailure(): void
    {
        $interfaceId = '\MissingInterface';
        $stubIface   = $this->makeInterface($interfaceId);

        $provider = $this->createMockReflectionProviderWithInterfaces([]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsParameterNamesCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('Interface', $result->getFailures()[$interfaceId]);
        $this->assertStringNotContainsString('Class', $result->getFailures()[$interfaceId]);
    }

    public function testInterfaceNotFoundInStubsIsFailure(): void
    {
        $interfaceId = '\MissingInterface';
        $reflIface   = $this->makeInterface($interfaceId);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([]);

        $result = (new InterfaceMethodsParameterNamesCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('Interface', $result->getFailures()[$interfaceId]);
    }

    // ── Name matching ─────────────────────────────────────────────────────────

    public function testMatchingParameterNamesPasses(): void
    {
        $interfaceId = '\Countable';
        $reflIface   = $this->makeInterface($interfaceId, [
            $this->makeMethod('offsetGet', [$this->makeParam('offset')]),
        ]);
        $stubIface   = $this->makeInterface($interfaceId, [
            $this->makeMethod('offsetGet', [$this->makeParam('offset')]),
        ]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsParameterNamesCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testParameterNameMismatchFails(): void
    {
        $interfaceId = '\Iterator';
        $methodId    = $interfaceId . '::offsetGet';

        $reflIface = $this->makeInterface($interfaceId, [
            $this->makeMethod('offsetGet', [$this->makeParam('offset')]),
        ]);
        $stubIface = $this->makeInterface($interfaceId, [
            $this->makeMethod('offsetGet', [$this->makeParam('key')]), // wrong name
        ]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsParameterNamesCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($methodId, $failures);
        $this->assertStringContainsString('$offset', $failures[$methodId]);
        $this->assertStringContainsString('$key', $failures[$methodId]);
        $this->assertStringNotContainsString('Class', $failures[$methodId]);
    }

    // ── Parent interface traversal ────────────────────────────────────────────

    public function testParameterNamesFromParentInterfaceAreChecked(): void
    {
        $childId = '\ChildInterface';

        $reflIface  = $this->makeInterface($childId, [
            $this->makeMethod('doWork', [$this->makeParam('value')]),
        ]);

        $parentStub = $this->makeInterface('\ParentInterface', [
            $this->makeMethod('doWork', [$this->makeParam('value')]),
        ]);
        $childStub  = $this->makeInterface($childId);
        $childStub->addParentInterface($parentStub);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$childStub]);

        $result = (new InterfaceMethodsParameterNamesCheck($provider))->run($stubs, $childId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Known problems ────────────────────────────────────────────────────────

    public function testInterfaceLevelKnownProblemSkipsAllMethods(): void
    {
        $interfaceId = '\SpecialInterface';
        $reflIface   = $this->makeInterface($interfaceId, [
            $this->makeMethod('doWork', [$this->makeParam('correct')]),
        ]);
        $stubIface   = $this->makeInterface($interfaceId, [
            $this->makeMethod('doWork', [$this->makeParam('wrong')]), // mismatch
        ]);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::INTERFACE_TYPE,
                entityId: $interfaceId,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETER_NAMES],
                versionRange: new PhpVersionRange(PhpVersions::PHP_8_0, PhpVersions::LATEST),
                reason: 'Interface-level param names skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsParameterNamesCheck($provider, $registry))->run($stubs, $interfaceId, '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertStringContainsString('skipped', $result->getSuccesses()[0]);
    }
}
