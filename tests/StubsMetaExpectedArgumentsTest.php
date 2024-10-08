<?php
declare(strict_types=1);

namespace StubTests;

use JetBrains\PhpStorm\Pure;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\Exception;
use StubTests\Model\PHPClass;
use StubTests\Model\PHPFunction;
use StubTests\Model\PHPMethod;
use StubTests\Model\StubsContainer;
use StubTests\Parsers\ExpectedFunctionArgumentsInfo;
use StubTests\Parsers\MetaExpectedArgumentsCollector;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use function array_key_exists;
use function array_map;
use function array_walk_recursive;
use function count;
use function method_exists;
use function property_exists;
use function str_starts_with;

class StubsMetaExpectedArgumentsTest extends AbstractBaseStubsTestCase
{
    private const string PSR_LOG_LOGGER_NAMESPACE_PREFIX = "\\Psr\\Log\\";
    private const string GUZZLE_HTTP_NAMESPACE_PREFIX = "\\GuzzleHttp\\";
    private const string ILLUMINATE_NAMESPACE_PREFIX = "\\Illuminate\\";

    /**
     * @var ExpectedFunctionArgumentsInfo[]
     */
    private static array $expectedArguments;

    /**
     * @var string[]
     */
    private static array $registeredArgumentsSet;

    /**
     * @var string[]
     */
    private static array $functionsFqns;

    /**
     * @var string[]
     */
    private static array $methodsFqns;

    /**
     * @var string[]
     */
    private static array $constantsFqns;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $argumentsCollector = new MetaExpectedArgumentsCollector();
        self::$expectedArguments = $argumentsCollector->getExpectedArgumentsInfos();
        self::$registeredArgumentsSet = $argumentsCollector->getRegisteredArgumentsSet();
        $stubs = PhpStormStubsSingleton::getPhpStormStubs();
        self::$functionsFqns = array_map(fn (PHPFunction $func) => $func->id, $stubs->getFunctions());
        self::$methodsFqns = self::getMethodsFqns($stubs);
        self::$constantsFqns = self::getConstantsFqns($stubs);
    }

    private static function flatten(array $array): array
    {
        $return = [];
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[$a] = $a;
        });
        return $return;
    }

    public static function getConstantsFqns(StubsContainer $stubs): array
    {
        $constants = array_map(fn ($constant) => $constant->name, $stubs->getConstants());
        foreach ($stubs->getClasses() as $class) {
            foreach ($class->constants as $classConstant) {
                $name = self::getClassMemberFqn($class->id, $classConstant->name);
                $constants[$name] = $name;
            }
        }
        return $constants;
    }

    public static function getMethodsFqns(StubsContainer $stubs): array
    {
        return self::flatten(
            array_map(fn (PHPClass $class) => array_map(fn (PHPMethod $method) => self::getClassMemberFqn($class->id, $method->name), $class->methods), $stubs->getClasses())
        );
    }

    public function testFunctionReferencesExists()
    {
        foreach (self::$expectedArguments as $argument) {
            $expr = $argument->getFunctionReference();
            if ($expr instanceof FuncCall) {
                $fqn = $expr->name->toCodeString();
                if (!str_starts_with($fqn, self::PSR_LOG_LOGGER_NAMESPACE_PREFIX) &&
                    !str_starts_with($fqn, self::GUZZLE_HTTP_NAMESPACE_PREFIX) &&
                    !str_starts_with($fqn, self::ILLUMINATE_NAMESPACE_PREFIX)) {
                    self::assertArrayHasKey($fqn, self::$functionsFqns, "Can't resolve function " . $fqn);
                }
            } elseif ($expr instanceof StaticCall) {
                if ((string)$expr->name !== '__construct') {
                    $fqn = self::getClassMemberFqn($expr->class->toCodeString(), (string)$expr->name);
                    if (!str_starts_with($fqn, self::PSR_LOG_LOGGER_NAMESPACE_PREFIX) &&
                        !str_starts_with($fqn, self::GUZZLE_HTTP_NAMESPACE_PREFIX) &&
                        !str_starts_with($fqn, self::ILLUMINATE_NAMESPACE_PREFIX)) {
                        self::assertArrayHasKey($fqn, self::$methodsFqns, "Can't resolve method " . $fqn);
                    }
                }
            } elseif ($expr instanceof ConstFetch) {
                $fqn = $expr->name->toCodeString();
                if (!str_starts_with($fqn, self::PSR_LOG_LOGGER_NAMESPACE_PREFIX) &&
                    !str_starts_with($fqn, self::GUZZLE_HTTP_NAMESPACE_PREFIX) &&
                    !str_starts_with($fqn, self::ILLUMINATE_NAMESPACE_PREFIX)) {
                    self::assertArrayHasKey($fqn, self::$constantsFqns, "Can't resolve constant " . $fqn);
                }
            }
            elseif ($expr !== null) {
                self::fail('First argument should be function reference or method reference, got: ' . $expr::class);
            }
        }
    }

    public function testConstantsExists()
    {
        foreach (self::$expectedArguments as $argument) {
            $expectedArguments = $argument->getExpectedArguments();
            self::assertNotEmpty($expectedArguments, 'Expected arguments should not be empty for ' . $argument);
            foreach ($expectedArguments as $constantReference) {
                if ($constantReference instanceof ClassConstFetch) {
                    $fqn = self::getClassMemberFqn($constantReference->class->toCodeString(), (string)$constantReference->name);
                    if (!str_starts_with($fqn, self::PSR_LOG_LOGGER_NAMESPACE_PREFIX)) {
                        self::assertArrayHasKey($fqn, self::$constantsFqns, "Can't resolve class constant " . $fqn);
                    }
                } elseif ($constantReference instanceof ConstFetch) {
                    $name = $constantReference->name->name;
                    self::assertContains($name, self::$constantsFqns, "Can't resolve constant " . $name);
                }
            }
        }
    }

    public function testRegisteredArgumentsSetExists()
    {
        foreach (self::$expectedArguments as $argument) {
            $usedArgumentsSet = [];
            foreach ($argument->getExpectedArguments() as $argumentsSet) {
                if ($argumentsSet instanceof FuncCall && (string)$argumentsSet->name === 'argumentsSet') {
                    $args = $argumentsSet->args;
                    self::assertGreaterThanOrEqual(1, count($args), 'argumentsSet call should provide set name');
                    if (property_exists($args[0]->value, 'value')) {
                        $name = $args[0]->value->value;
                    } else {
                        self::fail("Couldn't read name of arguments set");
                    }
                    self::assertContains($name, self::$registeredArgumentsSet, 'Can\'t find registered argument set: ' . $name);
                    self::assertArrayNotHasKey(
                        $name,
                        $usedArgumentsSet,
                        $name . ' argumentsSet used more then once for ' . self::getFqn($argument->getFunctionReference())
                    );
                    $usedArgumentsSet[$name] = $name;
                }
            }
        }
    }

    public function testStringLiteralsSingleQuoted()
    {
        foreach (self::$expectedArguments as $argument) {
            foreach ($argument->getExpectedArguments() as $literalArgument) {
                if ($literalArgument instanceof String_) {
                    self::assertEquals(
                        String_::KIND_SINGLE_QUOTED,
                        $literalArgument->getAttribute('kind'),
                        'String literals as expectedArguments should be single-quoted'
                    );
                }
            }
        }
    }

    public function testExpectedArgumentsAreUnique()
    {
        $functionsFqnsWithIndeces = [];
        foreach (self::$expectedArguments as $argument) {
            if ($argument->getIndex() < 0) {
                continue;
            }
            $functionReferenceFqn = self::getFqn($argument->getFunctionReference());
            $index = $argument->getIndex();
            if (array_key_exists($functionReferenceFqn, $functionsFqnsWithIndeces)) {
                self::assertNotContains($index, $functionsFqnsWithIndeces[$functionReferenceFqn], 'Expected arguments for ' . $functionReferenceFqn . ' with index ' . $index . ' already registered');
                $functionsFqnsWithIndeces[$functionReferenceFqn][] = $index;
            } else {
                $functionsFqnsWithIndeces[$functionReferenceFqn] = [$index];
            }
        }
    }

    public function testExpectedReturnValuesAreUnique()
    {
        $expectedReturnValuesFunctionsFqns = [];
        foreach (self::$expectedArguments as $argument) {
            if ($argument->getIndex() >= 0 || $argument->getFunctionReference() === null) {
                continue;
            }
            $functionReferenceFqn = self::getFqn($argument->getFunctionReference());
            self::assertArrayNotHasKey(
                $functionReferenceFqn,
                $expectedReturnValuesFunctionsFqns,
                'Expected return values for ' . $functionReferenceFqn . ' already registered'
            );
            $expectedReturnValuesFunctionsFqns[$functionReferenceFqn] = $functionReferenceFqn;
        }
    }

    public static function testRegisteredArgumentsSetAreUnique()
    {
        $registeredArgumentsSet = [];
        foreach (self::$registeredArgumentsSet as $name) {
            self::assertArrayNotHasKey($name, $registeredArgumentsSet, 'Set with name ' . $name . ' already registered');
            $registeredArgumentsSet[$name] = $name;
        }
    }

    public function testReferencesAreAbsolute()
    {
        foreach (self::$expectedArguments as $argument) {
            $expr = $argument->getFunctionReference();
            if ($expr !== null) {
                if ($expr instanceof StaticCall) {
                    $name = $expr->class;
                } elseif (property_exists($expr, 'name')) {
                    $name = $expr->name;
                } else {
                    self::fail("Couldn't read name of expression");
                }
                $originalName = $name->getAttribute('originalName');
                if (method_exists($originalName, 'isFullyQualified')) {
                    self::assertTrue(
                        $originalName->isFullyQualified(),
                        self::getFqn($expr) . ' should be fully qualified'
                    );
                } else {
                    self::fail('Could not check if name is fully qualified');
                }
            }
        }
    }

    #[Pure]
    private static function getClassMemberFqn(string $className, string $memberName): string
    {
        return $className . '.' . $memberName;
    }

    private static function getFqn(?Expr $expr): string
    {
        if ($expr instanceof StaticCall) {
            return self::getClassMemberFqn($expr->class->toCodeString(), (string)$expr->name);
        } elseif (property_exists($expr, 'name')) {
            return (string)$expr->name;
        } else {
            throw new Exception("Couldn't read a name of property with type {$expr->getType()}");
        }
    }
}
