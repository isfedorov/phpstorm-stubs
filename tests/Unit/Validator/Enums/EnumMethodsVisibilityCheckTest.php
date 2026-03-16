<?php

namespace StubTests\Unit\Validator\Enums;

use StubTests\Framework\Parsers\Entities\Model\Access\PrivateAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\ProtectedAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\PublicAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Runner\PhpVersions;
use StubTests\Sources\Validator\Enums\EnumMethodsVisibilityCheck;
use StubTests\Sources\Validator\ReflectionProviderInterface;
use StubTests\Unit\Validator\CheckTestCase;

class EnumMethodsVisibilityCheckTest extends CheckTestCase
{
    private function makeMethod(string $name, string $visibility = 'public'): PHPMethod
    {
        $method = new PHPMethod();
        $method->setName($name);
        $method->setAccess(match ($visibility) {
            'protected' => new ProtectedAccessModifier(),
            'private'   => new PrivateAccessModifier(),
            default     => new PublicAccessModifier(),
        });
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
        $check = new EnumMethodsVisibilityCheck();
        $this->assertTrue($check->supports(PhpVersions::PHP_8_1->value));
        $this->assertTrue($check->supports(PhpVersions::LATEST->value));
    }

    // ── Matching visibility ───────────────────────────────────────────────────

    public function testPublicMethodMatchPasses(): void
    {
        $enumId   = '\RoundingMode';
        $reflEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', 'public')]);
        $stubEnum = $this->makeEnum($enumId, [$this->makeMethod('cases', 'public')]);

        $provider = $this->createMockReflectionProviderWithEnums([$reflEnum]);
        $stubs    = $this->createMockStorageManager();
        $stubs->method('getEnums')->willReturn([$stubEnum]);

        $result = (new EnumMethodsVisibilityCheck($provider))->run($stubs, $enumId, '8.1');

        $this->assertFalse($result->hasFailures());
    }
}
