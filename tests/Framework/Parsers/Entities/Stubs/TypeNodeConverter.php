<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Parsers\Entities\Model\Types\NoType;
use StubTests\Sources\Parsers\Entities\Model\Types\NullableType;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Parsers\Entities\Model\Types\UnionType;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\TypeNode;

/**
 * Converts TypeNode (from stub AST) to type objects (StandaloneType, UnionType, NullableType, NoType).
 * This provides a unified type representation between stubs and reflection parsers.
 */
class TypeNodeConverter
{
    /**
     * Convert a TypeNode to a type object.
     *
     * @param TypeNode|null $typeNode The type node from stub AST
     * @return StandaloneType|UnionType|NullableType|NoType The corresponding type object
     */
    public function convert(?TypeNode $typeNode): StandaloneType|UnionType|NullableType|NoType
    {
        if ($typeNode === null) {
            return new NoType();
        }

        $typeString = $typeNode->toString();

        if ($typeString === '') {
            return new NoType();
        }

        return $this->parseTypeString($typeString);
    }

    /**
     * Parse a type string into type objects.
     * Handles union types (string|int), nullable types (string|null), and standalone types (string).
     *
     * @param string $typeString The type string to parse
     * @return StandaloneType|UnionType|NullableType|NoType
     */
    private function parseTypeString(string $typeString): StandaloneType|UnionType|NullableType|NoType
    {
        // Check if it's a union type (contains |)
        if (str_contains($typeString, '|')) {
            return $this->parseUnionType($typeString);
        }

        // Standalone type
        return new StandaloneType($typeString);
    }

    /**
     * Parse a union type string into UnionType or NullableType.
     * Examples: "string|int" -> UnionType, "string|null" -> NullableType
     *
     * @param string $typeString The union type string
     * @return UnionType|NullableType
     */
    private function parseUnionType(string $typeString): UnionType|NullableType
    {
        $types = explode('|', $typeString);
        $types = array_map('trim', $types);

        // Check if it's a nullable type (exactly 2 types, one is 'null')
        if (count($types) === 2 && in_array('null', $types, true)) {
            $nonNullType = $types[0] === 'null' ? $types[1] : $types[0];

            $nullableType = new NullableType();
            $nullableType->addBasicType(new StandaloneType($nonNullType));
            return $nullableType;
        }

        // General union type
        $unionType = new UnionType();
        foreach ($types as $type) {
            $unionType->addType(new StandaloneType($type));
        }

        return $unionType;
    }
}
