<?php

namespace StubTests\Sources\Parsers\Registries;

use StubTests\Sources\Parsers\Entities\Reflection\ReflectionClassParser;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionEnumParser;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionFunctionParser;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionInterfaceParser;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionModernConstantParser;

class EntityReflectionObjectParsersRegistry {
    private ReflectionClassParser $classParser;
    private ReflectionModernConstantParser $constantParser;
    private ReflectionFunctionParser $functionParser;
    private ReflectionInterfaceParser $interfaceParser;
    private ReflectionEnumParser $enumParser;

    public function __construct(
        ?ReflectionClassParser $classParser = null,
        ?ReflectionModernConstantParser $constantParser = null,
        ?ReflectionFunctionParser $functionParser = null,
        ?ReflectionInterfaceParser $interfaceParser = null,
        ?ReflectionEnumParser $enumParser = null
    ) {
        // Create parser instances once and reuse them
        $this->classParser = $classParser ?? new ReflectionClassParser();
        $this->constantParser = $constantParser ?? new ReflectionModernConstantParser();
        $this->functionParser = $functionParser ?? new ReflectionFunctionParser();
        $this->interfaceParser = $interfaceParser ?? new ReflectionInterfaceParser();
        $this->enumParser = $enumParser ?? new ReflectionEnumParser();
    }

    /**
     * Find the appropriate parser for the given reflection object
     * by calling canParse() on each parser.
     *
     * @param mixed $object The reflection object to find a parser for
     * @return ReflectionClassParser|ReflectionInterfaceParser|ReflectionEnumParser|ReflectionFunctionParser|ReflectionModernConstantParser|null
     */
    public function findParserForObject($object)
    {
        // Try parsers in order: enum, interface, class, function, constant
        // More specific checks should come first
        if ($this->enumParser->canParse($object)) {
            return $this->enumParser;
        }
        if ($this->interfaceParser->canParse($object)) {
            return $this->interfaceParser;
        }
        if ($this->classParser->canParse($object)) {
            return $this->classParser;
        }
        if ($this->functionParser->canParse($object)) {
            return $this->functionParser;
        }
        if ($this->constantParser->canParse($object)) {
            return $this->constantParser;
        }

        return null;
    }
}