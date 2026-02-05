<?php

namespace StubTests\Sources\Parsers\Registries;

use StubTests\Sources\Parsers\Entities\EntityType;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionClassParser;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionEnumParser;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionFunctionParser;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionModernConstantParser;

class EntityReflectionObjectParsersRegistry {

    public function findParser(EntityType $entityType)
    {
        if ($entityType === EntityType::A_CLASS) {
            return new ReflectionClassParser();
        } else if ($entityType === EntityType::CONSTANT) {
            return new ReflectionModernConstantParser();
        } else if ($entityType === EntityType::FUNCTION) {
            return new ReflectionFunctionParser();
        } else if ($entityType === EntityType::ENUM) {
            return new ReflectionEnumParser();
        }
        return null;
    }
}