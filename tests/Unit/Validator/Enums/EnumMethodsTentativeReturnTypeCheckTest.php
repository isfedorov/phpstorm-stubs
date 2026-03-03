<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumMethodsTentativeReturnTypeCheck;
use StubTests\Sources\Validator\KnownProblemsRegistry;
use StubTests\Unit\Validator\CheckTestCase;

class EnumMethodsTentativeReturnTypeCheckTest extends CheckTestCase
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

    private function makeEnumWithMethod(string $enumId, string $methodName, bool $tentative): PHPEnum
    {
        $method = new PHPMethod();
        $method->setName($methodName);
        $method->setHasTentativeReturnType($tentative);

        $enum = new PHPEnum();
        $enum->setId($enumId);
        $enum->setName(ltrim($enumId, '\\'));
        $enum->methods = [$method];
        return $enum;
    }

    private function makeReflection(array $enums): \StubTests\Sources\Validator\ReflectionProviderInterface
    {
        $provider = $this->createMock(\StubTests\Sources\Validator\ReflectionProviderInterface::class);
        $manager  = $this->createMockStorageManager();
        $manager->method('getEnums')->willReturn($enums);
        $provider->method('getReflection')->willReturn($manager);
        return $provider;
    }

    public function testSupportsPhp81AndAbove(): void
    {
        $check = new EnumMethodsTentativeReturnTypeCheck();
        $this->assertFalse($check->supports(PhpVersions::PHP_8_0->value));
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    public function testEnumNotFoundInReflectionIsFailure(): void
    {
        $enumId  = '\MyEnum';
        $stubs   = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$this->makeEnumWithMethod($enumId, 'cases', false)]);

        $result = (new EnumMethodsTentativeReturnTypeCheck($this->makeReflection([])))
            ->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in reflection data', $result->getFailures()[$enumId]);
    }

    public function testEnumNotFoundInStubsIsFailure(): void
    {
        $enumId = '\MyEnum';
        $stubs  = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([]);

        $result = (new EnumMethodsTentativeReturnTypeCheck($this->makeReflection([$this->makeEnumWithMethod($enumId, 'cases', false)])))
            ->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertStringContainsString('not found in stubs', $result->getFailures()[$enumId]);
    }

    public function testMatchingTentativeFlagsSucceed(): void
    {
        $enumId = '\MyEnum';

        $stubs = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$this->makeEnumWithMethod($enumId, 'current', true)]);

        $result = (new EnumMethodsTentativeReturnTypeCheck($this->makeReflection([$this->makeEnumWithMethod($enumId, 'current', true)])))
            ->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    public function testMismatchedTentativeFlagsFailure(): void
    {
        $enumId = '\MyEnum';

        $stubs = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$this->makeEnumWithMethod($enumId, 'current', false)]);

        $result = (new EnumMethodsTentativeReturnTypeCheck($this->makeReflection([$this->makeEnumWithMethod($enumId, 'current', true)])))
            ->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $this->assertArrayHasKey($enumId . '::current', $result->getFailures());
        $this->assertStringContainsString('tentative return type', $result->getFailures()[$enumId . '::current']);
    }
}
