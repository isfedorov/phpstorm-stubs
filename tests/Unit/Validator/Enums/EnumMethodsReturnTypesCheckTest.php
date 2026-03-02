<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumMethodsReturnTypesCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class EnumMethodsReturnTypesCheckTest extends CheckTestCase
{
    private function makeMethod(string $name, ?string $returnType = null): PHPMethod
    {
        $method = new PHPMethod();
        $method->setName($name);
        if ($returnType !== null) {
            $method->setReturnTypeFromSignature(new StandaloneType($returnType));
        }
        return $method;
    }

    private function makeEnum(string $id, array $methods = []): PHPEnum
    {
        $enum = new PHPEnum();
        $enum->setId($id);
        $enum->methods = $methods;
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
        $check = new EnumMethodsReturnTypesCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_7_0->value));
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    public function testDoesNotSupportPhpBefore70(): void
    {
        $check = new EnumMethodsReturnTypesCheck();
        $this->assertFalse($check->supports(PhpVersions::PHP_5_6->value));
    }

    // ── Return type matching ──────────────────────────────────────────────────

    public function testMatchingReturnTypePasses(): void
    {
        $enumId   = '\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', 'array')]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', 'array')]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodsReturnTypesCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }

    public function testReturnTypeMismatchFails(): void
    {
        $enumId   = '\RoundingMode';
        // Reflection says 'array', stub says 'int'
        $reflEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', 'array')]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', 'int')]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodsReturnTypesCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertTrue($result->hasFailures());
        $failures = $result->getFailures();
        $this->assertArrayHasKey($enumId . '::cases', $failures);
        $this->assertStringContainsString('Return type mismatch', $failures[$enumId . '::cases']);
        $this->assertStringNotContainsString('Class', $failures[$enumId . '::cases']);
    }

    public function testNoReturnTypeInReflectionPasses(): void
    {
        $enumId   = '\RoundingMode';
        // Reflection has no return type → check passes regardless of stub
        $reflEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', null)]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', 'array')]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodsReturnTypesCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }
}
