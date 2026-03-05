<?php

namespace StubTests\Unit\Validator\Interfaces;

use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Interfaces\InterfaceMethodsParameterDefaultValueCheck;
use StubTests\Unit\Validator\CheckTestCase;

class InterfaceMethodsParameterDefaultValueCheckTest extends CheckTestCase
{
    private InterfaceMethodsParameterDefaultValueCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new InterfaceMethodsParameterDefaultValueCheck();
    }

    private function makeParam(string $name, bool $hasDefault, mixed $defaultValue): PHPParameter
    {
        $param = new PHPParameter($name);
        $param->setHasDefaultValue($hasDefault);
        if ($hasDefault) {
            $param->setDefaultValue($defaultValue);
            $param->setIsOptional(true);
        }
        return $param;
    }

    private function createMockReflectionProviderWithInterfaces(array $interfaces = [])
    {
        $provider = $this->createMock(\StubTests\Sources\Validator\ReflectionProviderInterface::class);
        $manager  = $this->createMockStorageManager();
        $manager->method('getInterfaces')->willReturn($interfaces);
        $provider->method('getReflection')->willReturn($manager);
        return $provider;
    }

    private function createMockInterface(string $id, array $methods = []): PHPInterface
    {
        $iface = $this->getMockBuilder(PHPInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getName', 'getMethods', 'getParentInterfaces'])
            ->getMock();
        $iface->method('getId')->willReturn($id);
        $iface->method('getName')->willReturn(ltrim($id, '\\'));
        $iface->method('getMethods')->willReturn($methods);
        $iface->method('getParentInterfaces')->willReturn([]);
        return $iface;
    }

    public function testSupportsOnlyLatestPhpVersion(): void
    {
        $this->assertFalse($this->check->supports(PhpVersions::EARLIEST->value));
        $this->assertFalse($this->check->supports(PhpVersions::PHP_8_3->value));
        $this->assertTrue($this->check->supports(PhpVersions::LATEST->value));
    }

    public function testInterfaceNotFoundInReflectionIsFailure(): void
    {
        $ifaceId   = '\MyInterface';
        $stubIface = $this->createMockInterface($ifaceId);

        $provider = $this->createMockReflectionProviderWithInterfaces([]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsParameterDefaultValueCheck($provider))->run($stubs, $ifaceId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('Interface', $result->getFailures()[$ifaceId]);
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$ifaceId]);
    }

    public function testMatchingDefaultsSucceed(): void
    {
        $ifaceId    = '\MyInterface';
        $reflParams = [$this->makeParam('mode', true, 1)];
        $stubParams = [$this->makeParam('mode', true, 1)];

        $reflIface = $this->createMockInterface($ifaceId, [$this->createMockMethod('execute', $reflParams)]);
        $stubIface = $this->createMockInterface($ifaceId, [$this->createMockMethod('execute', $stubParams)]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsParameterDefaultValueCheck($provider))->run($stubs, $ifaceId, PhpVersions::LATEST->value);

        $this->assertFalse($result->hasFailures());
    }

    public function testMismatchedDefaultIsFailure(): void
    {
        $ifaceId    = '\MyInterface';
        $reflParams = [$this->makeParam('mode', true, 1)];
        $stubParams = [$this->makeParam('mode', true, 2)]; // wrong

        $reflIface = $this->createMockInterface($ifaceId, [$this->createMockMethod('execute', $reflParams)]);
        $stubIface = $this->createMockInterface($ifaceId, [$this->createMockMethod('execute', $stubParams)]);

        $provider = $this->createMockReflectionProviderWithInterfaces([$reflIface]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([$stubIface]);

        $result = (new InterfaceMethodsParameterDefaultValueCheck($provider))->run($stubs, $ifaceId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($ifaceId . '::execute', $result->getFailures());
        $this->assertStringContainsString('Interface', $result->getFailures()[$ifaceId . '::execute']);
    }

    public function testEntityTypeIsInterface(): void
    {
        $ifaceId = '\NoSuchInterface';

        $provider = $this->createMockReflectionProviderWithInterfaces([]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getInterfaces')->willReturn([]);

        $result = (new InterfaceMethodsParameterDefaultValueCheck($provider))->run($stubs, $ifaceId, PhpVersions::LATEST->value);

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('Interface ' . $ifaceId, $result->getFailures()[$ifaceId]);
    }
}
