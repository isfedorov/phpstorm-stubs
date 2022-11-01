<?php

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use JetBrains\PhpStorm\Internal\PhpStormStubsElementAvailable;
use JetBrains\PhpStorm\Internal\ReturnTypeContract as TypeContract;
use JetBrains\PhpStorm\Internal\TentativeType;
use JetBrains\PhpStorm\Language;
use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;

interface SomeParentInterface1
{
    #[NoReturn]
    public static function noReturnFoo(#[Language('PHP')] $phpScript);
}

interface SomeParentInterface2 extends SomeParentInterface1
{
    #[NoReturn]
    public static function noReturnFoo(#[Language('PHP')] $phpScript);
}

interface SomeInterface extends SomeParentInterface1
{
    #[Deprecated(reason: "Old", replacement: "MY_CONST_3", since: "7.2")]
    public const MY_CONST_1 = 1;

    /**
     * @since 7.1
     */
    const MY_CONST_2 = 1;
    const MY_CONST_3 = CONSTANT_TO_MIGRATE;

    #[Pure]
    #[Deprecated(reason: 'Depr', replacement: 'AnotherOne', since: '7.1')]
    #[PhpStormStubsElementAvailable(from: '5.3', to: '8.1')]
    #[ExpectedValues([CONSTANT_TO_MIGRATE, 2, 3])]
    #[TentativeType]
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
        #[TypeContract(true: 'int', false: 'int')]
        $affectingReturn = true
    );
}
