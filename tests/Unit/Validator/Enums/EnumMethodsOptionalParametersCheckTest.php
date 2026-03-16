<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumMethodsOptionalParametersCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class EnumMethodsOptionalParametersCheckTest extends CheckTestCase
{
    private function makeParam(string $name, bool $isOptional = false): PHPParameter
    {
        $param = new PHPParameter($name);
        $param->setIsOptional($isOptional);
        return $param;
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

    public function testSupportsAllPhpVersions(): void
    {
        $check = new EnumMethodsOptionalParametersCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Optional parameter matching ───────────────────────────────────────────

    public function testRequiredParamOnBothSidesPasses(): void
    {
        $enumId   = '\Dom\AdjacentPosition';
        $reflEnum = $this->makeEnum($enumId, [
            $this->makeMethod('from', [$this->makeParam('value', false)]),
        ]);
        $stubEnum = $this->makeEnum($enumId, [
            $this->makeMethod('from', [$this->makeParam('value', false)]),
        ]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodsOptionalParametersCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    public function testOptionalInReflectionButRequiredInStubFails(): void
    {
        $enumId   = '\MyEnum';
        // Reflection says optional, stub says required
        $reflParam = new PHPParameter('value');
        $reflParam->setIsOptional(true);

        $stubParam = new PHPParameter('value');
        $stubParam->setIsOptional(false);

        $reflMethod = new PHPMethod();
        $reflMethod->setName('doSomething');
        $reflMethod->setParameters([$reflParam]);

        $stubMethod = new PHPMethod();
        $stubMethod->setName('doSomething');
        $stubMethod->setParameters([$stubParam]);

        $reflEnum = $this->makeEnum($enumId, [$reflMethod]);
        $stubEnum = $this->makeEnum($enumId, [$stubMethod]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodsOptionalParametersCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertStringContainsString('optional', array_values($failures)[0]);
        $this->assertStringNotContainsString('Class', array_values($failures)[0]);
    }
}
