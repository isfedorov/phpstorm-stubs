<?php

namespace StubTests\Unit\Validator\Interfaces;

use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsTentativeReturnTypeCheck;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Unit\Validator\CheckTestCase;

class InterfaceMethodsTentativeReturnTypeCheckTest extends CheckTestCase
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

    private function makeInterface(string $id, string $methodName, bool $tentative): PHPInterface
    {
        $method = new PHPMethod();
        $method->setName($methodName);
        $method->setHasTentativeReturnType($tentative);

        $iface = new PHPInterface();
        $iface->setId($id);
        $iface->setName(ltrim($id, '\\'));
        $iface->methods = [$method];
        return $iface;
    }

    private function createMockReflectionProviderWithInterfaces(array $interfaces): \StubTests\Sources\Validator\ReflectionProviderInterface
    {
        $provider = $this->createMock(\StubTests\Sources\Validator\ReflectionProviderInterface::class);
        $manager  = $this->createMockStorageManager();
        $manager->method('getInterfaces')->willReturn($interfaces);
        $provider->method('getReflection')->willReturn($manager);
        return $provider;
    }

    public function testSupportsPhp81AndAbove(): void
    {
        $check = new InterfaceMethodsTentativeReturnTypeCheck();
        $this->assertFalse($check->supports(PhpVersions::PHP_8_0->value));
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    public function testInterfaceNotFoundInReflectionIsFailure(): void
    {
        $ifaceId = '\Iterator';
        $stubs   = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$this->makeInterface($ifaceId, 'current', false)]);

        $result = (new InterfaceMethodsTentativeReturnTypeCheck(
            $this->createMockReflectionProviderWithInterfaces([])
        ))->run($stubs, $ifaceId, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$ifaceId]);
    }

    public function testInterfaceNotFoundInStubsIsFailure(): void
    {
        $ifaceId = '\Iterator';
        $stubs   = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([]);

        $result = (new InterfaceMethodsTentativeReturnTypeCheck(
            $this->createMockReflectionProviderWithInterfaces([$this->makeInterface($ifaceId, 'current', true)])
        ))->run($stubs, $ifaceId, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in stubs', $result->getFailures()[$ifaceId]);
    }

    public function testMatchingTentativeFlagsSucceed(): void
    {
        $ifaceId = '\Iterator';
        $stubs   = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$this->makeInterface($ifaceId, 'current', true)]);

        $result = (new InterfaceMethodsTentativeReturnTypeCheck(
            $this->createMockReflectionProviderWithInterfaces([$this->makeInterface($ifaceId, 'current', true)])
        ))->run($stubs, $ifaceId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    public function testMismatchedTentativeFlagsFailure(): void
    {
        $ifaceId = '\Iterator';
        $stubs   = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$this->makeInterface($ifaceId, 'current', false)]);

        $result = (new InterfaceMethodsTentativeReturnTypeCheck(
            $this->createMockReflectionProviderWithInterfaces([$this->makeInterface($ifaceId, 'current', true)])
        ))->run($stubs, $ifaceId, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($ifaceId . '::current', $result->getFailures());
        $this->assertStringContainsString('tentative return type', $result->getFailures()[$ifaceId . '::current']);
    }

    public function testParentInterfaceTentativeMethodMismatchIsReported(): void
    {
        $parentId = '\Iterator';
        $childId  = '\RecursiveIterator';

        // Reflection reports 'current' as tentative for RecursiveIterator
        $reflChild = new PHPInterface();
        $reflChild->setId($childId);
        $reflChild->setName('RecursiveIterator');
        $currentMethod = new PHPMethod();
        $currentMethod->setName('current');
        $currentMethod->setHasTentativeReturnType(true);
        $reflChild->methods = [$currentMethod];

        // Stubs: RecursiveIterator has no methods but extends Iterator (stub)
        $parentStub = new PHPInterface();
        $parentStub->setId($parentId);
        $parentStub->setName('Iterator');
        $parentMethod = new PHPMethod();
        $parentMethod->setName('current');
        $parentMethod->setHasTentativeReturnType(false); // stub parent: not tentative → mismatch
        $parentStub->methods = [$parentMethod];

        $childStub = new PHPInterface();
        $childStub->setId($childId);
        $childStub->setName('RecursiveIterator');
        $childStub->methods = [];
        $childStub->setParentInterfaces([$parentStub]);

        $stubs = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$childStub]);

        $result = (new InterfaceMethodsTentativeReturnTypeCheck(
            $this->createMockReflectionProviderWithInterfaces([$reflChild])
        ))->run($stubs, $childId, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($childId . '::current', $result->getFailures());
    }
}
