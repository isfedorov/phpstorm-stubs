<?php

namespace StubTests\Framework\Parsers\Entities\Stubs\Types;

use StubTests\Sources\Parsers\Entities\Model\Types\NoType;
use StubTests\Sources\Parsers\Entities\Model\Types\NullableType;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Parsers\Entities\Model\Types\UnionType;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\TypeNode;

/**
 * Converts TypeNode (from stub AST) to type objects (StandaloneType, UnionType, NullableType, NoType).
 * This provides a unified type representation between stubs and reflection parsers.
 * Resolves imported class names to fully qualified names.
 */
class TypeNodeConverter
{
    private array $imports;
    private string $namespace;

    /**
     * @param array $imports Map of import aliases to fully qualified names
     * @param string $namespace Current namespace context (e.g., '\Dom' or '\\' for global)
     */
    public function __construct(array $imports = [], string $namespace = '\\')
    {
        $this->imports = $imports;
        $this->namespace = $namespace;
    }

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

        // Standalone type - resolve it using imports
        $resolvedType = $this->resolveTypeName($typeString);
        return new StandaloneType($resolvedType);
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
            
            // Resolve the non-null type
            $resolvedType = $this->resolveTypeName($nonNullType);

            $nullableType = new NullableType();
            $nullableType->addBasicType(new StandaloneType($resolvedType));
            return $nullableType;
        }

        //TODO: add support for union+intersection type type1|(type2&type3)

        // General union type - resolve each component
        $unionType = new UnionType();
        foreach ($types as $type) {
            $resolvedType = $this->resolveTypeName($type);
            $unionType->addType(new StandaloneType($resolvedType));
        }

        return $unionType;
    }

    /**
     * Resolve a type name using imports and namespace context to get the fully qualified name.
     *
     * @param string $typeName The type name to resolve (e.g., "Result", "int", "LDAP\Result", "Attr")
     * @return string The resolved type name with leading backslash (e.g., "\LDAP\Result", "int", "\Dom\Attr")
     */
    private function resolveTypeName(string $typeName): string
    {
        // Skip built-in types (they don't need resolution)
        $builtInTypes = [
            'int', 'string', 'bool', 'float', 'array', 'object', 'mixed',
            'void', 'never', 'null', 'false', 'true', 'callable', 'iterable',
            'resource', 'self', 'parent', 'static'
        ];

        if (in_array(strtolower($typeName), $builtInTypes, true)) {
            return $typeName;
        }

        // If it starts with backslash, it's already fully qualified
        if (str_starts_with($typeName, '\\')) {
            return $typeName;
        }

        // Check if it's an alias in imports
        if (isset($this->imports[$typeName])) {
            $resolved = $this->imports[$typeName];
            // Ensure it starts with backslash for FQN
            return str_starts_with($resolved, '\\') ? $resolved : '\\' . $resolved;
        }

        // If the type contains a namespace separator, it's a qualified name (not fully qualified)
        // e.g., "Dom\Attr" - prepend leading backslash
        if (str_contains($typeName, '\\')) {
            return '\\' . $typeName;
        }

        // Unqualified name - resolve relative to current namespace
        // e.g., in "namespace Dom;", "Attr" becomes "\Dom\Attr"
        if ($this->namespace === '\\') {
            // Global namespace
            return '\\' . $typeName;
        } else {
            // Prepend current namespace
            return $this->namespace . '\\' . $typeName;
        }
    }
}
