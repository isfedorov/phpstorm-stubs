<?php

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use JetBrains\PhpStorm\Internal\PhpStormStubsElementAvailable;
use JetBrains\PhpStorm\Internal\ReturnTypeContract;
use JetBrains\PhpStorm\Internal\TentativeType;
use JetBrains\PhpStorm\Language;
use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;

class SomeParentClass {}

/**
 * Error is the base class for all internal PHP error exceptions.
 * @link https://php.net/manual/en/class.error.php
 *
 */
#[Immutable]
#[Deprecated]
abstract class SomeClass extends SomeParentClass implements \SomeInterface
{
    /**
     * CONSTANT 1
     */
    public const CONSTANT1 = 1;
    public const CONSTANT2 = 2;
    public const CONSTANT3 = 3;
    public const CONSTANT4 = "CONSTANT4";

    #[Immutable(allowedWriteScope: Immutable::CONSTRUCTOR_WRITE_SCOPE)]
    #[ExpectedValues([CONSTANT_TO_MIGRATE, 2, 3])]
    public $property = CONSTANT_TO_MIGRATE;
    public int $prop2 = 1;

    /**
     * Some description
     * @param \stdClass $stdClass
     * @param bool $second
     * @param int<0, max> $int
     * @param $array
     * @param $affectingReturn
     * @return string[]
     */
    #[Pure]
    #[Deprecated(reason: 'Depr', replacement: 'AnotherOne', since: '7.1')]
    #[PhpStormStubsElementAvailable(from: '5.3', to: '8.1')]
    #[ExpectedValues([CONSTANT_TO_MIGRATE, 2, 3])]
    #[TentativeType]
    #[ArrayShape([0 => "string", 1 => "int", 2 => "string"])]
    public function testFoo(
        stdClass $stdClass,
        #[LanguageLevelTypeAware(['7.4' => 'int', '8.1' => 'bool'], default: 'mixed')]
        $second,
        int $int = CONSTANT_TO_MIGRATE,
        #[PhpStormStubsElementAvailable(from: '7.2', to: '8.1')]
        #[ExpectedValues([CONSTANT_TO_MIGRATE, 2, 3])]
        $third = 3,
        #[ArrayShape(['first' => 'int', 'second' => 'string'])]
        $array = [],
        string $separator = "\t",
        #[ReturnTypeContract(true: 'int', false: 'int')]
        $affectingReturn = true,
        $constants = SomeClass::CONSTANT1|SomeClass::CONSTANT2|SomeClass::CONSTANT3
    ) {}

    abstract public function abstractFoo();

    #[NoReturn]
    public static function noReturnFoo(#[Language('PHP')] $phpScript) {}

    #[TentativeType]
    public function __wakeup(): void
    {
        // TODO: Implement __wakeup() method.
    }
}
