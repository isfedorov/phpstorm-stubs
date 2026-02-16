<?php

namespace StubTests\Sources\Parsers\Entities\Stubs\Adapters\Nikic;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\ParserFactory;
use StubTests\Sources\Parsers\Entities\Stubs\NodeExtractorInterface;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\ClassNode;
use StubTests\Sources\Parsers\Entities\Stubs\Nodes\FunctionNode;

/**
 * Nikic/php-parser implementation of NodeExtractorInterface.
 * Extracts AST nodes from PHP stub code using nikic/php-parser and wraps them in adapter objects.
 */
class NikicNodeExtractor implements NodeExtractorInterface
{
    private \PhpParser\Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
    }

    public function extractFunction(string $stubCode): FunctionNode
    {
        $ast = $this->parser->parse($stubCode);
        $namespace = null;
        $functionNode = null;

        foreach ($ast as $node) {
            if ($node instanceof Namespace_) {
                $namespace = $node->name ? '\\' . $node->name->toString() : '\\';
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Function_) {
                        $functionNode = $stmt;
                        break 2;
                    }
                }
            } elseif ($node instanceof Function_) {
                $namespace = '\\';
                $functionNode = $node;
                break;
            }
        }

        if (!$functionNode) {
            throw new \RuntimeException('No function found in stub code');
        }

        $node = new NikicFunctionNode($functionNode);
        $node->setNamespace($namespace ?? '\\');
        return $node;
    }

    public function extractClass(string $stubCode): ClassNode
    {
        $ast = $this->parser->parse($stubCode);
        $namespace = null;
        $classNode = null;

        foreach ($ast as $node) {
            if ($node instanceof Namespace_) {
                $namespace = $node->name ? '\\' . $node->name->toString() : '\\';
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Class_) {
                        $classNode = $stmt;
                        break 2;
                    }
                }
            } elseif ($node instanceof Class_) {
                $namespace = '\\';
                $classNode = $node;
                break;
            }
        }

        if (!$classNode) {
            throw new \RuntimeException('No class found in stub code');
        }

        $node = new NikicClassNode($classNode);
        $node->setNamespace($namespace ?? '\\');
        return $node;
    }

    public function extractAllClasses(string $stubCode): array
    {
        $ast = $this->parser->parse($stubCode);
        $classes = [];
        $currentNamespace = '\\';

        foreach ($ast as $node) {
            if ($node instanceof Namespace_) {
                $currentNamespace = $node->name ? '\\' . $node->name->toString() : '\\';
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Class_) {
                        $classNode = new NikicClassNode($stmt);
                        $classNode->setNamespace($currentNamespace);
                        $classes[] = $classNode;
                    }
                }
            } elseif ($node instanceof Class_) {
                $classNode = new NikicClassNode($node);
                $classNode->setNamespace('\\');
                $classes[] = $classNode;
            }
        }

        return $classes;
    }

    public function extractAllFunctions(string $stubCode): array
    {
        $ast = $this->parser->parse($stubCode);
        $functions = [];
        $currentNamespace = '\\';

        foreach ($ast as $node) {
            if ($node instanceof Namespace_) {
                $currentNamespace = $node->name ? '\\' . $node->name->toString() : '\\';
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Function_) {
                        $functionNode = new NikicFunctionNode($stmt);
                        $functionNode->setNamespace($currentNamespace);
                        $functions[] = $functionNode;
                    }
                }
            } elseif ($node instanceof Function_) {
                $functionNode = new NikicFunctionNode($node);
                $functionNode->setNamespace('\\');
                $functions[] = $functionNode;
            }
        }

        return $functions;
    }

    public function extractAllInterfaces(string $stubCode): array
    {
        $ast = $this->parser->parse($stubCode);
        $interfaces = [];
        $currentNamespace = '\\';

        foreach ($ast as $node) {
            if ($node instanceof Namespace_) {
                $currentNamespace = $node->name ? '\\' . $node->name->toString() : '\\';
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Interface_) {
                        $interfaceNode = new NikicInterfaceNode($stmt);
                        $interfaceNode->setNamespace($currentNamespace);
                        $interfaces[] = $interfaceNode;
                    }
                }
            } elseif ($node instanceof Interface_) {
                $interfaceNode = new NikicInterfaceNode($node);
                $interfaceNode->setNamespace('\\');
                $interfaces[] = $interfaceNode;
            }
        }

        return $interfaces;
    }

    public function extractAllEnums(string $stubCode): array
    {
        $ast = $this->parser->parse($stubCode);
        $enums = [];
        $currentNamespace = '\\';

        foreach ($ast as $node) {
            if ($node instanceof Namespace_) {
                $currentNamespace = $node->name ? '\\' . $node->name->toString() : '\\';
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Enum_) {
                        $enumNode = new NikicEnumNode($stmt);
                        $enumNode->setNamespace($currentNamespace);
                        $enums[] = $enumNode;
                    }
                }
            } elseif ($node instanceof Enum_) {
                $enumNode = new NikicEnumNode($node);
                $enumNode->setNamespace('\\');
                $enums[] = $enumNode;
            }
        }

        return $enums;
    }

    public function extractAllDefineConstants(string $stubCode): array
    {
        $ast = $this->parser->parse($stubCode);
        $constants = [];
        $currentNamespace = '\\';

        foreach ($ast as $node) {
            if ($node instanceof Namespace_) {
                $currentNamespace = $node->name ? '\\' . $node->name->toString() : '\\';
                $this->extractDefinesFromStatements($node->stmts, $currentNamespace, $constants);
            } else {
                $this->extractDefinesFromStatements([$node], $currentNamespace, $constants);
            }
        }

        return $constants;
    }

    public function extractAllModernConstants(string $stubCode): array {
        $ast = $this->parser->parse($stubCode);
        $constants = [];
        $currentNamespace = '\\';

        foreach ($ast as $node) {
            if ($node instanceof Namespace_) {
                $currentNamespace = $node->name ? '\\' . $node->name->toString() : '\\';
                $this->extractConstFromStatements($node->stmts, $currentNamespace, $constants);
            } else {
                $this->extractConstFromStatements([$node], $currentNamespace, $constants);
            }
        }

        return $constants;
    }

    private function extractDefinesFromStatements(array $stmts, string $namespace, array &$constants): void
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof \PhpParser\Node\Stmt\Expression &&
                $stmt->expr instanceof FuncCall &&
                $stmt->expr->name instanceof Name &&
                $stmt->expr->name->toString() === 'define') {

                $constantNode = new NikicConstantDefinitionNode($stmt->expr);
                $constantNode->setNamespace($namespace);
                $constants[] = $constantNode;
            }
        }
    }

    private function extractConstFromStatements(array $stmts, string $namespace, array &$constants): void
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof \PhpParser\Node\Stmt\Const_) {
                // Handle multiple constants in single statement: const A = 1, B = 2, C = 3
                foreach ($stmt->consts as $const) {
                    $constantNode = new NikicGlobalConstantNode($const);
                    $constantNode->setNamespace($namespace);
                    $constants[] = $constantNode;
                }
            }
        }
    }
}
