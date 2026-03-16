<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumMethodsParameterNamesCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class EnumMethodsParameterNamesCheckTest extends CheckTestCase
{
    private function makeParam(string $name): PHPParameter
    {
        return new PHPParameter($name);
    }

    private function makeMethod(string $name, array $params = []): PHPMethod
    {
        $method = new PHPMethod();
        $method->setName($name);
        $method->setParameters($params);
        return $method;
    }

    private function makeEnum(string $id, array $methods = []): PHPEnum
    {
        $enum = new PHPEnum();
        $enum->setId($id);
        $enum->setMethods($methods);
        return $enum;
    }

    private function createMockReflectionProviderWithEnums(array $enums = []): ReflectionProviderInterface
    {
        $provider = $this->createMock(ReflectionProviderInterface::class);
        $manager  = $this->createMockStorageManager();
        $manager->method('getEnums')->willReturn($enums);
        $provider->method('getReflection')->willReturn($manager);
        return $provider;
    }

    // ── supports() ────────────────────────────────────────────────────────────

    public function testSupportsPhp80AndAbove(): void
    {
        $check = new EnumMethodsParameterNamesCheck();
        $this->assertFalse($check->supports(PhpVersions::PHP_5_6->value));
        $this->assertFalse($check->supports(PhpVersions::PHP_7_4->value));
        $this->assertTrue($check->supports(PhpVersions::PHP_8_0->value));
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Not found ─────────────────────────────────────────────────────────────

    public function testEnumNotFoundInReflectionIsFailure(): void
    {
        $enumId   = '\Dom\AdjacentPosition';
        $stubEnum = $this->makeEnum($enumId);

        $provider = $this->createMockReflectionProviderWithEnums([]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodsParameterNamesCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('Enum', $result->getFailures()[$enumId]);
        $this->assertStringNotContainsString('Class', $result->getFailures()[$enumId]);
    }

    public function testEnumNotFoundInStubsIsFailure(): void
    {
        $enumId   = '\Dom\AdjacentPosition';
        $reflEnum = $this->makeEnum($enumId);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([]);

        $result = (new EnumMethodsParameterNamesCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('Enum', $result->getFailures()[$enumId]);
    }

    // ── Name matching ─────────────────────────────────────────────────────────

    public function testMatchingParameterNamesPasses(): void
    {
        $enumId   = '\Dom\AdjacentPosition';
        $reflEnum = $this->makeEnum($enumId, [
            $this->makeMethod('from', [$this->makeParam('value')]),
        ]);
        $stubEnum = $this->makeEnum($enumId, [
            $this->makeMethod('from', [$this->makeParam('value')]),
        ]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodsParameterNamesCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    public function testParameterNameMismatchFails(): void
    {
        $enumId   = '\Dom\AdjacentPosition';
        $reflEnum = $this->makeEnum($enumId, [
            $this->makeMethod('from', [$this->makeParam('value')]),
        ]);
        $stubEnum = $this->makeEnum($enumId, [
            $this->makeMethod('from', [$this->makeParam('val')]), // wrong name
        ]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodsParameterNamesCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($enumId . '::from', $failures);
        $this->assertStringContainsString('$value', $failures[$enumId . '::from']);
        $this->assertStringContainsString('$val', $failures[$enumId . '::from']);
        $this->assertStringNotContainsString('Class', $failures[$enumId . '::from']);
    }
}
