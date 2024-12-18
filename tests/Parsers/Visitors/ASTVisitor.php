<?php
declare(strict_types=1);

namespace StubTests\Parsers\Visitors;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeVisitorAbstract;
use StubTests\Model\CommonUtils;
use StubTests\Model\PHPClass;
use StubTests\Model\PHPClassConstant;
use StubTests\Model\PHPConstant;
use StubTests\Model\PHPConstantDeclaration;
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
        public array &$childEntitiesToAddLater,
        protected bool $isStubCore = false,
        public ?string $sourceFilePath = null
    ) {}

    public function enterNode(Node $node): void
    {
        if ($node instanceof Function_) {
            $function = new PHPFunction()->readObjectFromStubNode($node);
            $function->getOrCreateStubSpecificProperties()->sourceFilePath = $this->sourceFilePath;
            if ($this->isStubCore) {
                $function->getOrCreateStubSpecificProperties()->stubBelongsToCore = true;
            }
            $this->stubs->addFunction($function);
        } elseif ($node instanceof Node\Stmt\EnumCase) {
            $constant = new PHPEnumCase()->readObjectFromStubNode($node);
            $constant->getOrCreateStubSpecificProperties()->sourceFilePath = $this->sourceFilePath;
            if ($this->isStubCore) {
                $constant->getOrCreateStubSpecificProperties()->stubBelongsToCore = true;
            }
            $this->childEntitiesToAddLater['enumCases'][] = $constant;
        } elseif ($node instanceof Node\Stmt\ClassConst) {
            $constantDeclaration = new PHPConstantDeclaration()->readObjectFromStubNode($node);
            /** @var PHPClassConstant|PHPConstant $constant */
            foreach ($constantDeclaration->constants as $constant) {
                $constant->getOrCreateStubSpecificProperties()->sourceFilePath = $this->sourceFilePath;
                if ($this->isStubCore) {
                    $constant->getOrCreateStubSpecificProperties()->stubBelongsToCore = true;
                }
                $this->childEntitiesToAddLater['classConstants'][] = $constant;
            }
        } elseif ($node instanceof Node\Stmt\Const_) {
            $constantDeclaration = new PHPConstantDeclaration()->readObjectFromStubNode($node);
            /** @var PHPClassConstant|PHPConstant $constant */
            foreach ($constantDeclaration->constants as $constant) {
                $constant->getOrCreateStubSpecificProperties()->sourceFilePath = $this->sourceFilePath;
                if ($this->isStubCore) {
                    $constant->getOrCreateStubSpecificProperties()->stubBelongsToCore = true;
                }
                $this->stubs->addConstant($constant);
            }
        } elseif ($node instanceof FuncCall) {
            if ((string)$node->name === 'define') {
                $constant = new PHPDefineConstant()->readObjectFromStubNode($node);
                $constant->getOrCreateStubSpecificProperties()->sourceFilePath = $this->sourceFilePath;
                if ($this->isStubCore) {
                    $constant->getOrCreateStubSpecificProperties()->stubBelongsToCore = true;
                }
                $this->stubs->addConstant($constant);
            }
        } elseif ($node instanceof ClassMethod) {
            $method = new PHPMethod()->readObjectFromStubNode($node);
            $method->getOrCreateStubSpecificProperties()->sourceFilePath = $this->sourceFilePath;
            if ($this->isStubCore) {
                $method->getOrCreateStubSpecificProperties()->stubBelongsToCore = true;
            }
            $this->childEntitiesToAddLater['methods'][] = $method;
        } elseif ($node instanceof Interface_) {
            $interface = new PHPInterface()->readObjectFromStubNode($node);
            $interface->getOrCreateStubSpecificProperties()->sourceFilePath = $this->sourceFilePath;
            if ($this->isStubCore) {
                $interface->getOrCreateStubSpecificProperties()->stubBelongsToCore = true;
            }
            $this->stubs->addInterface($interface);
        } elseif ($node instanceof Class_) {
            $class = new PHPClass()->readObjectFromStubNode($node);
            $class->getOrCreateStubSpecificProperties()->sourceFilePath = $this->sourceFilePath;
            if ($this->isStubCore) {
                $class->getOrCreateStubSpecificProperties()->stubBelongsToCore = true;
            }
            $this->stubs->addClass($class);
        } elseif ($node instanceof Enum_) {
            $enum = new PHPEnum()->readObjectFromStubNode($node);
            $enum->getOrCreateStubSpecificProperties()->sourceFilePath = $this->sourceFilePath;
            if ($this->isStubCore) {
                $enum->getOrCreateStubSpecificProperties()->stubBelongsToCore = true;
            }
            $this->stubs->addEnum($enum);
        } elseif ($node instanceof Node\Stmt\Property) {
            $property = new PHPProperty()->readObjectFromStubNode($node);
            $property->getOrCreateStubSpecificProperties()->sourceFilePath = $this->sourceFilePath;
            if ($this->isStubCore) {
                $property->getOrCreateStubSpecificProperties()->stubBelongsToCore = true;
            }
            $this->childEntitiesToAddLater['properties'][] = $property;
        }
    }

    public function convertParentInterfacesFromStringsToObjects(PHPInterface $interface): array
    {
        $parents = [];
        if (empty($interface->parentInterfaces)) {
            return $parents;
        }
        /** @var string $parentInterface */
        foreach ($interface->parentInterfaces as $parentInterface) {
            $parents[] = $parentInterface;
            $alreadyParsedInterface = $this->stubs->getInterface(
                $parentInterface,
                $interface->getOrCreateStubSpecificProperties()->stubBelongsToCore ? null : $interface->getOrCreateStubSpecificProperties()->sourceFilePath,
                false
            );
            if ($alreadyParsedInterface !== null) {
                foreach ($this->convertParentInterfacesFromStringsToObjects($alreadyParsedInterface) as $value) {
                    $parents[] = $value;
                }
            }
        }
        return $parents;
    }

    public function convertImplementedInterfacesFromStringsToObjects(PHPClass $class): array
    {
        $interfaces = [];
        /** @var string $interface */
        foreach ($class->interfaces as $interface) {
            $interfaces[] = $interface;
            $alreadyParsedInterface = $this->stubs->getInterface(
                $interface,
                $class->getOrCreateStubSpecificProperties()->stubBelongsToCore ? null : $class->getOrCreateStubSpecificProperties()->sourceFilePath,
                false
            );
            if ($alreadyParsedInterface !== null) {
                $interfaces[] = $alreadyParsedInterface->parentInterfaces;
            }
        }
        if ($class->parentClass === null) {
            return $interfaces;
        }
        $alreadyParsedClass = $this->stubs->getClass(
            $class->parentClass,
            $class->getOrCreateStubSpecificProperties()->stubBelongsToCore ? null : $class->getOrCreateStubSpecificProperties()->sourceFilePath,
            false
        );
        if ($alreadyParsedClass !== null) {
            $inherited = $this->convertImplementedInterfacesFromStringsToObjects($alreadyParsedClass);
            $interfaces[] = CommonUtils::flattenArray($inherited, false);
        }
        return $interfaces;
    }
}
