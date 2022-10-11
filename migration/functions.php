<?php

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\ExpectedValues as EV;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware as TypeAware;
use JetBrains\PhpStorm\Internal\PhpStormStubsElementAvailable;
use JetBrains\PhpStorm\Internal\ReturnTypeContract as TypeContract;
use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;

/**
 * Some description
 * @param stdClass $stdClass
 * @param bool $second
 * @param int $int
 * @param $array
 * @param $affectingReturn
 * @return void
 */
#[Pure]
#[Deprecated(reason: 'Depr', replacement: 'AnotherOne', since: '7.1')]
#[PhpStormStubsElementAvailable(from: '5.3', to: '8.1')]
#[ExpectedValues([CONSTANT_TO_MIGRATE, 2, 3])]
#[LanguageLevelTypeAware(['7.4' => 'int|string', '8.1' => 'bool|stdClass'], default: 'resource')]
function testFoo(
    stdClass $stdClass,
    #[LanguageLevelTypeAware(['7.4' => 'int', '8.1' => 'bool'], default: 'mixed')]
    bool $second,
    int $int = CONSTANT_TO_MIGRATE,
    #[PhpStormStubsElementAvailable(from: '7.2', to: '8.1')]
    #[ExpectedValues([CONSTANT_TO_MIGRATE, 2, 3])]
    $third = 3,
    #[ExpectedValues([
        'boolean', 'integer', 'double', 'string', 'array', 'object', 'resource', 'NULL', 'unknown type', 'resource (closed)'
    ])]
    $fourth,
    $special = "\n",
    $special2 = "\\",
    $class = stdClass::class,
    string $datetime = 'now',
    #[TypeContract(true: 'int', false: 'int')]
    $affectingReturn = true,
    #[PhpStormStubsElementAvailable(from: '5.3', to: '7.2')] int $scale,
    #[PhpStormStubsElementAvailable(from: '7.3')] #[LanguageLevelTypeAware(['8.0' => 'int|null'], default: 'int')] $scale = null,
    #[ArrayShape(['first' => 'int', 'second' => 'string'])]
    $array = [],
    #[TypeAware(['8.0' => 'int'], default: '')] #[EV([Collator::PRIMARY])] $strength = 0,
    #[PhpStormStubsElementAvailable(from: '5.3', to: '7.4')] $arrays, array ...$arrays,
): bool|stdClass {}

#[NoReturn]
function anotherFoo() {}
