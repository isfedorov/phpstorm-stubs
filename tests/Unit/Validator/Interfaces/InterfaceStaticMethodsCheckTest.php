<?php

namespace StubTests\Unit\Validator\Interfaces;

use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Interfaces\InterfaceStaticMethodsCheck;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class InterfaceStaticMethodsCheckTest extends CheckTestCase
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

    public function testSupportsAllPhpVersions(): void
    {
        $check = new InterfaceStaticMethodsCheck();
        $this->assertTrue($check->supports(PhpVersions::EARLIEST->value));
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

        $result = (new InterfaceStaticMethodsCheck($provider))->run($stubs, $interfaceId, '8.0');

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

        $result = (new InterfaceStaticMethodsCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('Interface', $result->getFailures()[$interfaceId]);
        $this->assertStringNotContainsString('Class', $result->getFailures()[$interfaceId]);
    }

    // ── Static flag matching ──────────────────────────────────────────────────

    public function testBothStaticIsSuccess(): void
    {
        // UnitEnum::cases() is a real-world example of a static interface method.
        $interfaceId = '\UnitEnum';
        $reflIface   = $this->makeInterface($interfaceId, [$this->makeMethod('cases', true)]);
        $stubIface   = $this->makeInterface($interfaceId, [$this->makeMethod('cases', true)]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceStaticMethodsCheck($provider))->run($stubs, $interfaceId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    public function testBothNonStaticIsSuccess(): void
    {
        $interfaceId = '\Iterator';
        $reflIface   = $this->makeInterface($interfaceId, [$this->makeMethod('current', false)]);
        $stubIface   = $this->makeInterface($interfaceId, [$this->makeMethod('current', false)]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceStaticMethodsCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertFalse($result->hasFailures());
    }

    public function testReflectionStaticStubNonStaticIsFailure(): void
    {
        $interfaceId = '\UnitEnum';
        $reflIface   = $this->makeInterface($interfaceId, [$this->makeMethod('cases', true)]);
        $stubIface   = $this->makeInterface($interfaceId, [$this->makeMethod('cases', false)]);  // mismatch

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceStaticMethodsCheck($provider))->run($stubs, $interfaceId, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($interfaceId . '::cases', $failures);
        $this->assertStringContainsString('static', $failures[$interfaceId . '::cases']);
    }

    public function testReflectionNonStaticStubStaticIsFailure(): void
    {
        $interfaceId = '\Iterator';
        $reflIface   = $this->makeInterface($interfaceId, [$this->makeMethod('current', false)]);
        $stubIface   = $this->makeInterface($interfaceId, [$this->makeMethod('current', true)]);  // mismatch

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceStaticMethodsCheck($provider))->run($stubs, $interfaceId, '8.0');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($interfaceId . '::current', $result->getFailures());
    }

    // ── Parent interface traversal ────────────────────────────────────────────

    public function testStaticMethodFromParentInterfaceIsCounted(): void
    {
        // BackedEnum extends UnitEnum. cases() is declared static in UnitEnum.
        $backedEnumId   = '\BackedEnum';
        $reflIface      = $this->makeInterface($backedEnumId, [
            $this->makeMethod('cases', true),  // inherited from UnitEnum
            $this->makeMethod('from', true),
        ]);

        $unitEnumStub   = $this->makeInterface('\UnitEnum', [$this->makeMethod('cases', true)]);
        $backedEnumStub = $this->makeInterface($backedEnumId, [$this->makeMethod('from', true)]);
        $backedEnumStub->addParentInterface($unitEnumStub);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$backedEnumStub]);

        $result = (new InterfaceStaticMethodsCheck($provider))->run($stubs, $backedEnumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    public function testStaticMismatchInParentInterfaceIsReported(): void
    {
        $backedEnumId   = '\BackedEnum';
        $reflIface      = $this->makeInterface($backedEnumId, [
            $this->makeMethod('cases', true),  // reflection: static
        ]);

        $unitEnumStub   = $this->makeInterface('\UnitEnum', [
            $this->makeMethod('cases', false),  // stub: non-static → mismatch
        ]);
        $backedEnumStub = $this->makeInterface($backedEnumId);
        $backedEnumStub->addParentInterface($unitEnumStub);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$backedEnumStub]);

        $result = (new InterfaceStaticMethodsCheck($provider))->run($stubs, $backedEnumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($backedEnumId . '::cases', $result->getFailures());
    }

    // ── Known problems — interface level ──────────────────────────────────────

    public function testInterfaceLevelKnownProblemSkipsAllMethods(): void
    {
        $interfaceId = '\SpecialInterface';
        $reflIface   = $this->makeInterface($interfaceId, [$this->makeMethod('create', true)]);
        $stubIface   = $this->makeInterface($interfaceId, [$this->makeMethod('create', false)]);

        $knownProblemsProvider = $this->createMock(\StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider::class);
        $knownProblemsProvider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::INTERFACE_TYPE,
                entityId: $interfaceId,
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_STATIC_METHODS],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'Interface-level skip'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($knownProblemsProvider);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceStaticMethodsCheck($provider, $registry))->run($stubs, $interfaceId, '8.0');

        $this->assertFalse($result->hasFailures());
        $this->assertStringContainsString('skipped', $result->getSuccesses()[0]);
    }
}
