<?php
declare(strict_types=1);

namespace StubTests\Parsers\Visitors;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeVisitorAbstract;
use StubTests\Model\PHPClass;
use StubTests\Model\PHPConst;
use StubTests\Model\PHPDefineConstant;
use StubTests\Model\PHPEnum;
use StubTests\Model\PHPEnumCase;
use StubTests\Model\PHPFunction;
use StubTests\Model\PHPInterface;
use StubTests\Model\PHPMethod;
use StubTests\Model\PHPProperty;
use StubTests\Model\StubsContainer;

class ASTVisitor extends NodeVisitorAbstract
{
    public function __construct(
        protected StubsContainer $stubs,
        protected bool $shouldSuitCurrentPhpVersion,
        protected bool $isStubCore = false,
        public ?string $sourceFilePath = null,
        public ?string $sourceFileName = null
    ) {}

    /**
     * @throws Exception
     */
    public function enterNode(Node $node): void
    {
        if ($node instanceof Function_) {
            $function = (new PHPFunction($this->shouldSuitCurrentPhpVersion))->readObjectFromStubNode($node);
            $function->sourceFilePath = $this->sourceFilePath;
            $function->sourceFileName = $this->sourceFileName;
            if ($this->isStubCore) {
                $function->stubBelongsToCore = true;
            }
            $this->stubs->addFunction($function);
        } elseif ($node instanceof Node\Stmt\EnumCase) {
            $constant = (new PHPEnumCase())->readObjectFromStubNode($node);
            $constant->sourceFilePath = $this->sourceFilePath;
            if ($this->isStubCore) {
                $constant->stubBelongsToCore = true;
            }
            if ($this->stubs->getEnum($constant->parentName, $this->sourceFilePath, false) !== null) {
                $this->stubs->getEnum($constant->parentName, $this->sourceFilePath, false)->addEnumCase($constant);
            }
        } elseif ($node instanceof Const_) {
            $constant = (new PHPConst($this->shouldSuitCurrentPhpVersion))->readObjectFromStubNode($node);
            $constant->sourceFilePath = $this->sourceFilePath;
            $constant->sourceFileName = $this->sourceFileName;
            if ($this->isStubCore) {
                $constant->stubBelongsToCore = true;
            }
            if ($constant->parentName === null) {
                $this->stubs->addConstant($constant);
            } elseif ($this->stubs->getEnum($constant->parentName, $this->sourceFilePath, false) !== null) {
                $this->stubs->getEnum($constant->parentName, $this->sourceFilePath, false)->addConstant($constant);
            } elseif ($this->stubs->getClass($constant->parentName, $this->sourceFilePath, false) !== null) {
                $this->stubs->getClass($constant->parentName, $this->sourceFilePath, false)->addConstant($constant);
            } elseif ($this->stubs->getInterface($constant->parentName, $this->sourceFilePath, false) !== null) {
                $this->stubs->getInterface($constant->parentName, $this->sourceFilePath, false)->addConstant($constant);
            }
        } elseif ($node instanceof FuncCall) {
            if ((string)$node->name === 'define') {
                $constant = (new PHPDefineConstant($this->shouldSuitCurrentPhpVersion))->readObjectFromStubNode($node);
                $constant->sourceFilePath = $this->sourceFilePath;
                $constant->sourceFileName = $this->sourceFileName;
                if ($this->isStubCore) {
                    $constant->stubBelongsToCore = true;
                }
                $this->stubs->addConstant($constant);
            }
        } elseif ($node instanceof ClassMethod) {
            $method = (new PHPMethod($this->shouldSuitCurrentPhpVersion))->readObjectFromStubNode($node);
            $method->sourceFilePath = $this->sourceFilePath;
            $method->sourceFileName = $this->sourceFileName;
            if ($this->isStubCore) {
                $method->stubBelongsToCore = true;
            }
            if ($this->stubs->getEnum($method->parentName, $this->sourceFilePath, false) !== null) {
                $this->stubs->getEnum($method->parentName, $this->sourceFilePath, false)->addMethod($method);
            }
            elseif ($this->stubs->getClass($method->parentName, $this->sourceFilePath, false) !== null) {
                $this->stubs->getClass($method->parentName, $this->sourceFilePath, false)->addMethod($method);
            } elseif ($this->stubs->getInterface($method->parentName, $this->sourceFilePath, false) !== null) {
                $this->stubs->getInterface($method->parentName, $this->sourceFilePath, false)->addMethod($method);
            }
        } elseif ($node instanceof Interface_) {
            $interface = (new PHPInterface($this->shouldSuitCurrentPhpVersion))->readObjectFromStubNode($node);
            $interface->sourceFilePath = $this->sourceFilePath;
            $interface->sourceFileName = $this->sourceFileName;
            if ($this->isStubCore) {
                $interface->stubBelongsToCore = true;
            }
            $this->stubs->addInterface($interface);
        } elseif ($node instanceof Class_) {
            $class = (new PHPClass($this->shouldSuitCurrentPhpVersion))->readObjectFromStubNode($node);
            $class->sourceFilePath = $this->sourceFilePath;
            $class->sourceFileName = $this->sourceFileName;
            if ($this->isStubCore) {
                $class->stubBelongsToCore = true;
            }
            $this->stubs->addClass($class);
        } elseif ($node instanceof Enum_) {
            $enum = (new PHPEnum())->readObjectFromStubNode($node);
            $enum->sourceFilePath = $this->sourceFilePath;
            if ($this->isStubCore) {
                $enum->stubBelongsToCore = true;
            }
            $this->stubs->addEnum($enum);
        } elseif($node instanceof Node\Stmt\Namespace_) {
            
        }
        /*elseif ($node instanceof Node\Stmt\Property) {
            $property = (new PHPProperty())->readObjectFromStubNode($node);
            $property->sourceFilePath = $this->sourceFilePath;
            $property->sourceFileName = $this->sourceFileName;
            if ($this->isStubCore) {
                $property->stubBelongsToCore = true;
            }

            if ($this->stubs->getClass($property->parentName, $this->sourceFilePath, false) !== null) {
                $this->stubs->getClass($property->parentName, $this->sourceFilePath, false)->addProperty($property);
            }
        }*/
    }
}
