<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumMethodsParameterTypesCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class EnumMethodsParameterTypesCheckTest extends CheckTestCase
{
    private function makeTypedParam(string $name, string $type): PHPParameter
    {
        $param = new PHPParameter($name);
        $param->setType(new StandaloneType($type));
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

    public function testSupportsPhp70AndAbove(): void
    {
        $check = new EnumMethodsParameterTypesCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_7_0->value));
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    public function testDoesNotSupportPhpBefore70(): void
    {
        $check = new EnumMethodsParameterTypesCheck();
        $this->assertFalse($check->supports(PhpVersions::PHP_5_6->value));
    }

    // ── Matching parameter types ──────────────────────────────────────────────

    public function testMatchingParameterTypePasses(): void
    {
        $enumId   = '\Dom\AdjacentPosition';
        $reflEnum = $this->makeEnum($enumId, [
            $this->makeMethod('from', [$this->makeTypedParam('value', 'int')]),
        ]);
        $stubEnum = $this->makeEnum($enumId, [
            $this->makeMethod('from', [$this->makeTypedParam('value', 'int')]),
        ]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodsParameterTypesCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    public function testParameterTypeMismatchFails(): void
    {
        $enumId   = '\Dom\AdjacentPosition';
        $reflEnum = $this->makeEnum($enumId, [
            $this->makeMethod('from', [$this->makeTypedParam('value', 'int')]),
        ]);
        $stubEnum = $this->makeEnum($enumId, [
            $this->makeMethod('from', [$this->makeTypedParam('value', 'string')]),
        ]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodsParameterTypesCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($enumId . '::from', $failures);
        $this->assertStringContainsString('type', $failures[$enumId . '::from']);
        $this->assertStringNotContainsString('Class', $failures[$enumId . '::from']);
    }
}
