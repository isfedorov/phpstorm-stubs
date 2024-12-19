<?php

namespace StubTests\Model;

use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Node\Stmt\Enum_;
use StubTests\Model\Predicats\ConstantsFilterPredicateProvider;
use StubTests\Parsers\Helpers\AttributesHelper;
use StubTests\Parsers\Helpers\IdentifierHelper;
use StubTests\Parsers\Helpers\TypeHelper;
use StubTests\Parsers\ParserUtils;

class PHPEnum extends PHPClass
{
    public $enumCases = [];

    /**
     * @param \ReflectionEnum $reflectionObject
     * @return static
     */
    public function readObjectFromReflection($reflectionObject)
    {
        $this->name = $reflectionObject->getShortName();
        $this->interfaces = $reflectionObject->getInterfaceNames();
        $this->isFinal = $reflectionObject->isFinal();
        if (!empty($reflectionObject->getNamespaceName())) {
            $this->namespace = "\\" . $reflectionObject->getNamespaceName();
        }
        $this->fqnBasedId = "$this->namespace\\$this->name";
        if (method_exists($reflectionObject, 'isReadOnly')) {
            $this->isReadonly = $reflectionObject->isReadOnly();
        }
        foreach ($reflectionObject->getMethods() as $method) {
            if ($method->getDeclaringClass()->getShortName() !== $this->name) {
                continue;
            }
            $parsedMethod = (new PHPMethod())->readObjectFromReflection($method);
            $this->addMethod($parsedMethod);
        }

        if (method_exists($reflectionObject, 'getReflectionConstants')) {
            foreach ($reflectionObject->getReflectionConstants() as $constant) {
                if ($constant->isEnumCase()) {
                    $enumCase = (new PHPEnumCase())->readObjectFromReflection($constant);
                    $this->addEnumCase($enumCase);
                } else {
                    $parsedConstant = (new PHPClassConstant())->readObjectFromReflection($constant);
                    $this->addConstant($parsedConstant);
                }
            }
        }

        foreach ($reflectionObject->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() !== $this->name) {
                continue;
            }
            $parsedProperty = (new PHPProperty())->readObjectFromReflection($property);
            $this->addProperty($parsedProperty);
        }

        return $this;
    }

    /**
     * @param Enum_ $node
     * @return static
     */
    public function readObjectFromStubNode($node)
    {
        $this->fqnBasedId = IdentifierHelper::getFQN($node);
        $this->name = IdentifierHelper::getShortName($node);
        $this->namespace = rtrim(str_replace((string)$node->name, "", "\\" . $node->namespacedName), '\\');
        $this->getOrCreateStubSpecificProperties()->availableVersionsRangeFromAttribute = AttributesHelper::findAvailableVersionsRangeFromAttribute($node->attrGroups);
        $this->collectTags($node);
        $this->checkDeprecation($node);
        if (!empty($node->extends)) {
            $this->parentClass = IdentifierHelper::getShortName($node->extends);
        }
        if (!empty($node->implements)) {
            foreach ($node->implements as $interfaceObject) {
                $interfaceFQN = '';
                foreach ($interfaceObject->getParts() as $interface) {
                    $interfaceFQN .= "\\$interface";
                }
                $this->interfaces[] = ltrim($interfaceFQN, "\\");
            }
        }
        if ($node->getDocComment() !== null) {
            $docBlock = DocBlockFactory::createInstance()->create($node->getDocComment()->getText());
            /** @var PropertyRead[] $properties */
            $properties = array_merge(
                $docBlock->getTagsByName('property-read'),
                $docBlock->getTagsByName('property')
            );
            foreach ($properties as $property) {
                $propertyName = $property->getVariableName();
                assert($propertyName !== '', "@property name is empty in class $this->name");
                $newProperty = new PHPProperty($this->fqnBasedId);
                $newProperty->is_static = false;
                $newProperty->access = 'public';
                $newProperty->name = $propertyName;
                $newProperty->parentId = $this->name;
                $newProperty->typesFromSignature = TypeHelper::convertParsedTypeToArray($property->getType());
                assert(
                    !array_key_exists($propertyName, $this->properties),
                    "Property '$propertyName' is already declared in class '$this->name'"
                );
                $this->properties[$propertyName] = $newProperty;
            }
        }
        $this->getOrCreateStubSpecificProperties()->stubObjectHash = spl_object_hash($this);
        return $this;
    }

    public function readMutedProblems($jsonData) {}

    public function addEnumCase(PHPEnumCase $parsedEnumCase)
    {
        if (isset($parsedEnumCase->name)) {
            if (array_key_exists($parsedEnumCase->name, $this->enumCases)) {
                $amount = count(array_filter(
                    $this->enumCases,
                    function ($nextCase) use ($parsedEnumCase) {
                        return $nextCase->name === $parsedEnumCase->name;
                    }
                ));
                $this->enumCases[$parsedEnumCase->name . '_duplicated_' . $amount] = $parsedEnumCase;
            } else {
                $this->enumCases[$parsedEnumCase->name] = $parsedEnumCase;
            }
        }
    }

    public function getCase($caseName, $filterCallback = null)
    {
        if ($filterCallback === null) {
            $filterCallback = ConstantsFilterPredicateProvider::getDefaultSuitableEnumCases($caseName);
        }
        $enumCases = array_filter($this->enumCases, $filterCallback);
        return array_pop($enumCases);
    }
}
