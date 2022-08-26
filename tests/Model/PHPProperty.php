<?php

namespace StubTests\Model;

use Exception;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Stmt\Property;
use ReflectionProperty;
use stdClass;
use function is_array;
use function method_exists;
use function sprintf;
use function strval;

class PHPProperty extends BasePHPElement
{
    /** @var string[] */
    public $typesFromSignature = [];

    /** @var string[][] */
    public $typesFromAttribute = [];

    /** @var string[] */
    public $typesFromPhpDoc = [];
    public $access = '';
    public $isStatic = false;
    public $parentName;
    public $isReadonly = false;
    public $defaultValue = null;

    /**
     * @param string|null $parentName
     */
    public function __construct($parentName = null)
    {
        $this->parentName = $parentName;
    }

    /**
     * @param ReflectionProperty $reflectionObject
     * @return static
     */
    public function readObjectFromReflection($reflectionObject)
    {
        $this->name = $reflectionObject->getName();
        if ($reflectionObject->isProtected()) {
            $access = 'protected';
        } elseif ($reflectionObject->isPrivate()) {
            $access = 'private';
        } else {
            $access = 'public';
        }
        $this->access = $access;
        $this->isStatic = $reflectionObject->isStatic();
        if (method_exists($reflectionObject, 'getType')) {
            $this->typesFromSignature = self::getReflectionTypeAsArray($reflectionObject->getType());
        }
        if (method_exists($reflectionObject, 'isReadonly')) {
            $this->isReadonly = $reflectionObject->isReadOnly();
        }
        if (method_exists($reflectionObject, 'hasDefaultValue') &&
            method_exists($reflectionObject, 'getDefaultValue') &&
            $reflectionObject->hasDefaultValue()) {
            $this->defaultValue = $reflectionObject->getDefaultValue();
        }
        return $this;
    }

    /**
     * @param Property $node
     * @return static
     */
    public function readObjectFromStubNode($node)
    {
        $this->name = $node->props[0]->name->name;
        $this->collectTags($node);
        $this->isStatic = $node->isStatic();
        if ($node->isProtected()) {
            $access = 'protected';
        } elseif ($node->isPrivate()) {
            $access = 'private';
        } else {
            $access = 'public';
        }
        $this->attributes = $node->attrGroups;
        $this->access = $access;
        $this->isReadonly = $node->isReadonly();
        $this->typesFromSignature = self::convertParsedTypeToArray($node->type);
        $this->typesFromAttribute = self::findTypesFromAttribute($node->attrGroups);
        foreach ($this->varTags as $varTag) {
            $this->typesFromPhpDoc = explode('|', (string)$varTag->getType());
        }

        $parentNode = $node->getAttribute('parent');
        if ($parentNode !== null) {
            $this->parentName = self::getFQN($parentNode);
        }
        $this->defaultValue = self::getDefaultValueOfProperty($node);
        $this->collectTags($node);
        return $this;
    }

    /**
     * @param stdClass|array $jsonData
     * @throws Exception
     */
    public function readMutedProblems($jsonData)
    {
        foreach ($jsonData as $property) {
            if ($property->name === $this->name && !empty($property->problems)) {
                foreach ($property->problems as $problem) {
                    switch ($problem->description) {
                        case 'missing property':
                            $this->mutedProblems[StubProblemType::STUB_IS_MISSED] = $problem->versions;
                            break;
                        case 'wrong readonly':
                            $this->mutedProblems[StubProblemType::WRONG_READONLY] = $problem->versions;
                            break;
                        default:
                            throw new Exception("Unexpected value $problem->description");
                    }
                }
            }
        }
    }

    /**
     * @param Property $node
     * @return string|null
     */
    private static function getDefaultValueOfProperty(Property $node) {
        if($node->props[0]->default !== null) {
            if ($node->props[0]->default instanceof ConstFetch) {
                return (string)$node->props[0]->default->name;
            }elseif ($node->props[0]->default instanceof ClassConstFetch) {
                return sprintf("\%s::%s", $node->props[0]->default->class, $node->props[0]->default->name);
            } elseif ($node->props[0]->default instanceof UnaryMinus) {
                return '-' . $node->props[0]->default->expr->value;
            } elseif (is_array($node->props[0]->default) || $node->props[0]->default instanceof Array_) {
                return '[]';
            } else {
                return strval($node->props[0]->default->value);
            }
        } else {
            return null;
        }
    }
}
