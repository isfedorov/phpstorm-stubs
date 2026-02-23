<?php

namespace StubTests\Sources\Parsers\Entities\Stubs;

use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ClassNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ConstantDefinitionNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ConstantNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\EnumNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\FunctionNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\InterfaceNode;

/**
 * Interface for extracting AST nodes from PHP stub code.
 * Implementations handle specific parser libraries (e.g., nikic/php-parser).
 */
interface NodeExtractorInterface
{
    /**
     * Extracts a function node from stub code.
     * The namespace is set on the returned node.
     *
     * @param string $stubCode The PHP stub code to parse
     * @return FunctionNode Function node with namespace set
     * @throws \RuntimeException If no function is found
     */
    public function extractFunction(string $stubCode): FunctionNode;

    /**
     * Extracts a class node from stub code.
     * The namespace is set on the returned node.
     *
     * @param string $stubCode The PHP stub code to parse
     * @return ClassNode Class node with namespace set
     * @throws \RuntimeException If no class is found
     */
    public function extractClass(string $stubCode): ClassNode;

    /**
     * Extracts all class nodes from stub code.
     * The namespace is set on each returned node.
     *
     * @param string $stubCode The PHP stub code to parse
     * @return ClassNode[] Array of class nodes with namespace set
     */
    public function extractAllClasses(string $stubCode): array;

    /**
     * Extracts all class nodes with their import context from stub code.
     * Returns array of ['node' => ClassNode, 'imports' => array] for each class.
     *
     * @param string $stubCode The PHP stub code to parse
     * @return array Array of ['node' => ClassNode, 'imports' => array]
     */
    public function extractAllClassesWithImports(string $stubCode): array;

    /**
     * Extracts all function nodes from stub code.
     * The namespace is set on each returned node.
     *
     * @param string $stubCode The PHP stub code to parse
     * @return FunctionNode[] Array of function nodes with namespace set
     */
    public function extractAllFunctions(string $stubCode): array;

    /**
     * Extracts all interface nodes from stub code.
     * The namespace is set on each returned node.
     *
     * @param string $stubCode The PHP stub code to parse
     * @return InterfaceNode[] Array of interface nodes with namespace set
     */
    public function extractAllInterfaces(string $stubCode): array;

    /**
     * Extracts all interface nodes with their import context from stub code.
     * Returns array of ['node' => InterfaceNode, 'imports' => array] for each interface.
     *
     * @param string $stubCode The PHP stub code to parse
     * @return array Array of ['node' => InterfaceNode, 'imports' => array]
     */
    public function extractAllInterfacesWithImports(string $stubCode): array;

    /**
     * Extracts all enum nodes from stub code.
     * The namespace is set on each returned node.
     *
     * @param string $stubCode The PHP stub code to parse
     * @return EnumNode[] Array of enum nodes with namespace set
     */
    public function extractAllEnums(string $stubCode): array;

    /**
     * Extracts all enum nodes with their import context from stub code.
     * Returns array of ['node' => EnumNode, 'imports' => array] for each enum.
     *
     * @param string $stubCode The PHP stub code to parse
     * @return array Array of ['node' => EnumNode, 'imports' => array]
     */
    public function extractAllEnumsWithImports(string $stubCode): array;

    /**
     * Extracts all define() constant nodes from stub code.
     * The namespace is set on each returned node.
     *
     * @param string $stubCode The PHP stub code to parse
     * @return ConstantDefinitionNode[] Array of constant nodes with namespace set
     */
    public function extractAllDefineConstants(string $stubCode): array;

    /**
     * Extracts all const A = 1; constant nodes from stub code.
     * The namespace is set on each returned node.
     *
     * @param string $stubCode The PHP stub code to parse
     * @return ConstantNode[] Array of constant nodes with namespace set
     */
    public function extractAllModernConstants(string $stubCode): array;
}
