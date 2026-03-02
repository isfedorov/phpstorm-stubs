<?php

namespace StubTests\Unit\Validator;

use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\InterfaceMethodsParameterTypesCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Sources\Validator\ReflectionProviderInterface;

class InterfaceMethodsParameterTypesCheckTest extends CheckTestCase
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

    private function makeParam(string $name, mixed $type = null): PHPParameter
    {
        $param = new PHPParameter($name);
        if ($type !== null) {
            $param->setType($type);
        }
        return $param;
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

    public function testSupportsPhp70AndAbove(): void
    {
        $check = new InterfaceMethodsParameterTypesCheck();
        $this->assertFalse($check->supports(PhpVersions::EARLIEST->value));
        $this->assertFalse($check->supports(PhpVersions::PHP_5_6->value));
        $this->assertTrue($check->supports(PhpVersions::PHP_7_0->value));
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

        $result = (new InterfaceMethodsParameterTypesCheck($provider))->run($stubs, $interfaceId, '8.0');

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

        $result = (new InterfaceMethodsParameterTypesCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('Interface', $result->getFailures()[$interfaceId]);
        $this->assertStringNotContainsString('Class', $result->getFailures()[$interfaceId]);
    }

    // ── Parameter type matching ───────────────────────────────────────────────

    public function testInterfaceWithNoMethodsSucceeds(): void
    {
        $interfaceId = '\Countable';
        $reflIface   = $this->makeInterface($interfaceId);
        $stubIface   = $this->makeInterface($interfaceId);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsParameterTypesCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    public function testMatchingParameterTypeIsSuccess(): void
    {
        $interfaceId = '\Iterator';
        $stringType  = $this->createType('string');

        $reflIface = $this->makeInterface($interfaceId, [
            $this->makeMethod('offsetGet', [$this->makeParam('offset', $stringType)]),
        ]);
        $stubIface = $this->makeInterface($interfaceId, [
            $this->makeMethod('offsetGet', [$this->makeParam('offset', $stringType)]),
        ]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsParameterTypesCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testMismatchedParameterTypeIsFailure(): void
    {
        $interfaceId = '\Iterator';
        $methodId    = $interfaceId . '::offsetGet';

        $reflIface = $this->makeInterface($interfaceId, [
            $this->makeMethod('offsetGet', [$this->makeParam('offset', $this->createType('string'))]),
        ]);
        $stubIface = $this->makeInterface($interfaceId, [
            $this->makeMethod('offsetGet', [$this->makeParam('offset', $this->createType('int'))]),  // wrong type
        ]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsParameterTypesCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($methodId, $failures);
        $this->assertStringNotContainsString('Class', $failures[$methodId]);
    }

    // ── Parent interface traversal ────────────────────────────────────────────

    public function testParameterTypeFromParentInterfaceIsCounted(): void
    {
        $childId    = '\ChildInterface';
        $stringType = $this->createType('string');

        $reflIface  = $this->makeInterface($childId, [
            $this->makeMethod('offsetGet', [$this->makeParam('offset', $stringType)]),
        ]);

        $parentStub = $this->makeInterface('\ParentInterface', [
            $this->makeMethod('offsetGet', [$this->makeParam('offset', $stringType)]),
        ]);
        $childStub  = $this->makeInterface($childId);
        $childStub->addParentInterface($parentStub);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$childStub]);

        $result = (new InterfaceMethodsParameterTypesCheck($provider))->run($stubs, $childId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Known problems ────────────────────────────────────────────────────────

    public function testInterfaceLevelKnownProblemSkipsAllMethods(): void
    {
        $interfaceId = '\SpecialInterface';
        $reflIface   = $this->makeInterface($interfaceId, [
            $this->makeMethod('doWork', [$this->makeParam('value', $this->createType('string'))]),
        ]);
        $stubIface   = $this->makeInterface($interfaceId, [
            $this->makeMethod('doWork', [$this->makeParam('value', $this->createType('int'))]),  // mismatch
        ]);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::INTERFACE_TYPE,
                entityId: $interfaceId,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::PHP_7_0, PhpVersions::LATEST),
                reason: 'Interface-level skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsParameterTypesCheck($provider, $registry))->run($stubs, $interfaceId, '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertStringContainsString('skipped', $result->getSuccesses()[0]);
    }
}
