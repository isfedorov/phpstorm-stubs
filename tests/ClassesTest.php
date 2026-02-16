<?php

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\Runner;
use StubTests\Sources\Validator\ClassExistsCheck;

class ClassesTest extends TestCase
{
    public static function phpVersionProvider(): iterable
    {
        $reflector = new ReflectionClass(static::class);
        foreach ($reflector->getMethods() as $method) {
            $attrs = $method->getAttributes(PhpVersionRange::class);
            foreach ($attrs as $attr) {
                $range = $attr->newInstance();
                $allVersions = ['5.6','7.0','7.1','7.2','7.3','7.4','8.0','8.1','8.2','8.3','8.4'];
                foreach ($allVersions as $v) {
                    if ($range->includes($v)) {
                        $reflection = Runner::getReflection($v);
                        /** @var PHPClass $class */
                        foreach ($reflection->getClasses() as $class) {
                            yield "{$method->getName()}_{$class->getId()}_$v" => [$method->getName(), $class->getId(), $v];
                        }
                    }
                }
            }
        }
    }

    #[DataProvider('phpVersionProvider')]
    #[Test]
    public function runCheck(string $methodName, string $classId, string $phpVersion): void
    {
        $this->$methodName($classId, $phpVersion);
    }

    #[PhpVersionRange('5.6','8.4')]
    public function checkClassExistsInStubs(string $classId, string $phpVersion): void
    {
        $stubs = Runner::getStubs();

        // В реальной системе можно зарегистрировать несколько Check’ов
        $checks = [
            new ClassExistsCheck(),
        ];

        foreach ($checks as $check) {

            $results = $check->run($stubs, $classId, $phpVersion);

            // Ассерты — можно в отдельном методе
            $failures = $results->getFailures();
            $this->assertEmpty(
                $failures,
                "PHP {$phpVersion}: Some classes from reflection are missing in stubs:\n" . implode("\n", $failures)
            );
        }
    }
}
