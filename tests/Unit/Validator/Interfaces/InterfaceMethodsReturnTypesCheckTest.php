<?php

namespace StubTests\Unit\Validator\Interfaces;

use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsReturnTypesCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class InterfaceMethodsReturnTypesCheckTest extends CheckTestCase
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

    private function makeMethod(
        string $name,
        ?StandaloneType $returnType = null,
        ?string $sinceVersion = null,
        ?string $removedVersion = null
    ): PHPMethod {
        $method = new PHPMethod();
        $method->setName($name);
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

    private function makeInterface(string $id, array $methods = []): PHPInterface
    {
        $iface = new PHPInterface();
        $iface->setId($id);
        $iface->setName(ltrim($id, '\\'));
        $iface->setMethods($methods);
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
        $check = new InterfaceMethodsReturnTypesCheck();
        $this->assertFalse($check->supports(PhpVersions::EARLIEST->value));
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

        $result = (new InterfaceMethodsReturnTypesCheck($provider))->run($stubs, $interfaceId, '8.0');

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

        $result = (new InterfaceMethodsReturnTypesCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('Interface', $result->getFailures()[$interfaceId]);
        $this->assertStringNotContainsString('Class', $result->getFailures()[$interfaceId]);
    }

    // ── Return type matching ──────────────────────────────────────────────────

    public function testMatchingReturnTypeIsSuccess(): void
    {
        $interfaceId = '\Iterator';
        $returnType  = new StandaloneType('bool');

        $reflIface = $this->makeInterface($interfaceId, [$this->makeMethod('valid', $returnType)]);
        $stubIface = $this->makeInterface($interfaceId, [$this->makeMethod('valid', $returnType)]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsReturnTypesCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testMismatchedReturnTypeIsFailure(): void
    {
        $interfaceId  = '\Iterator';
        $methodId     = $interfaceId . '::valid';

        $reflIface = $this->makeInterface($interfaceId, [
            $this->makeMethod('valid', new StandaloneType('bool')),
        ]);
        $stubIface = $this->makeInterface($interfaceId, [
            $this->makeMethod('valid', new StandaloneType('int')),  // wrong type
        ]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsReturnTypesCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($methodId, $failures);
        $this->assertStringNotContainsString('Class', $failures[$methodId]);
    }

    public function testInterfaceWithNoMethodsSucceeds(): void
    {
        $interfaceId = '\Countable';
        $reflIface   = $this->makeInterface($interfaceId);
        $stubIface   = $this->makeInterface($interfaceId);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsReturnTypesCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertEquals(1, $result->getSuccessCount());
    }

    // ── Parent interface traversal ────────────────────────────────────────────

    public function testReturnTypeFromParentInterfaceIsCounted(): void
    {
        $childId    = '\ChildInterface';
        $returnType = new StandaloneType('bool');

        $reflIface  = $this->makeInterface($childId, [$this->makeMethod('valid', $returnType)]);

        $parentStub = $this->makeInterface('\ParentInterface', [$this->makeMethod('valid', $returnType)]);
        $childStub  = $this->makeInterface($childId);
        $childStub->addParentInterface($parentStub);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$childStub]);

        $result = (new InterfaceMethodsReturnTypesCheck($provider))->run($stubs, $childId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    // ── Known problems ────────────────────────────────────────────────────────

    public function testInterfaceLevelKnownProblemSkipsAllMethods(): void
    {
        $interfaceId = '\SpecialInterface';
        $reflIface   = $this->makeInterface($interfaceId, [
            $this->makeMethod('getValue', new StandaloneType('string')),
        ]);
        $stubIface   = $this->makeInterface($interfaceId, [
            $this->makeMethod('getValue', new StandaloneType('int')),  // mismatch
        ]);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::INTERFACE_TYPE,
                entityId: $interfaceId,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::RETURN_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::PHP_7_0, PhpVersions::LATEST),
                reason: 'Interface-level skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsReturnTypesCheck($provider, $registry))->run($stubs, $interfaceId, '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertStringContainsString('skipped', $result->getSuccesses()[0]);
    }
}
