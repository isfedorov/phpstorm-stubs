<?php

namespace StubTests\Sources\Parsers\Registries;

use StubTests\Sources\Parsers\Entities\EntityType;
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

    public function findParser(EntityType $entityType)
    {
        return match ($entityType) {
            EntityType::A_CLASS => $this->classParser,
            EntityType::CONSTANT => $this->constantParser,
            EntityType::FUNCTION => $this->functionParser,
            EntityType::INTERFACE => $this->interfaceParser,
            EntityType::ENUM => $this->enumParser,
            default => null,
        };
    }
}