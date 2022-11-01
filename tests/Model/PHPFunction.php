<?php

namespace StubTests\Model;

use Exception;
use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\Internal\TentativeType;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Compound;
use PhpParser\Comment\Doc;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\MagicConst;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use RuntimeException;
use stdClass;
use StubTests\Parsers\DocFactoryProvider;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use function array_filter;
use function array_pop;
use function array_push;
use function implode;
use function json_encode;
use function preg_match;
use function property_exists;
use function sizeof;
use function strval;

class PHPFunction extends BasePHPElement
{
    /**
     * @var PHPParameter[]
     */
    public $parameters = [];

    /** @var string[] */
    public $returnTypesFromPhpDoc = [];

    /** @var string[][] */
    public $returnTypesFromAttribute = [];

    /** @var string[] */
    public $returnTypesFromSignature = [];
    public $hasTentativeReturnType = false;

    /**
     * @param mixed $defaultValue
     * @param PHPClass|PHPInterface|null $contextClass
     * @return float|bool|int|string|null
     * @throws RuntimeException
     * @throws \PHPUnit\Framework\Exception
     */
    public function getStringRepresentationOfDefaultParameterValue(
        $defaultValue,
        $contextClass = null,
        $preserveConstantNamesInsteadOfValues = false
    )
    {
        if ($defaultValue instanceof ConstFetch) {
            $defaultValueName = (string)$defaultValue->name;
            if ($defaultValueName !== 'false' && $defaultValueName !== 'true' && $defaultValueName !== 'null') {
                $constant = PhpStormStubsSingleton::getPhpStormStubs()->getConstant($defaultValueName);
                $value = $preserveConstantNamesInsteadOfValues ? $constant->name : $constant->value;
            } else {
                $value = $defaultValueName;
            }
        } elseif ($defaultValue instanceof String_ || $defaultValue instanceof DNumber) {
            if (
                preg_match( '/\R/', $defaultValue->value) === 1 ||
                preg_match( '/\t/', $defaultValue->value) === 1 ||
                preg_match( '/\\\\/', $defaultValue->value) === 1
            )
            {
                $value = json_encode($defaultValue->value);
            } else {
                $value = "'" . $defaultValue->value . "'";
            }
        } elseif ($defaultValue instanceof LNumber) {
            $value = $defaultValue->value;
        } elseif ($defaultValue instanceof BitwiseOr) {
            $constants = [];
            $this->combineUnionDefaultValues($defaultValue, $constants, $contextClass, $preserveConstantNamesInsteadOfValues);
            $value = implode('|', $constants);
        } elseif ($defaultValue instanceof UnaryMinus && property_exists($defaultValue->expr, 'value')) {
            $value = '-' . $defaultValue->expr->value;
        } elseif ($defaultValue instanceof ClassConstFetch) {
            $value = $this->getClassConstantDefaultValue($defaultValue, $contextClass, $preserveConstantNamesInsteadOfValues);
        } elseif ($defaultValue === null) {
            $value = "null";
        } elseif (is_array($defaultValue) || $defaultValue instanceof Array_) {
            $value = '[]';
        } elseif ($defaultValue instanceof MagicConst) {
            $value = $defaultValue->getName();
        } else {
            $value = strval($defaultValue);
        }
        return $value;
    }

    /**
     * @param $defaultValue
     * @param array $constants
     */
    public function combineUnionDefaultValues(
        $defaultValue,
        array &$result,
        $contextClass,
        $preserveConstantNamesInsteadOfValues
    )
    {
        if ($defaultValue->left instanceof BitwiseOr) {
            $this->combineUnionDefaultValues(
                $defaultValue->left,
                $result,
                $contextClass,
                $preserveConstantNamesInsteadOfValues
            );
        }
        if ($defaultValue->left instanceof ConstFetch) {
            $constants = array_filter(
                PhpStormStubsSingleton::getPhpStormStubs()->getConstants(),
                function (PHPConst $const) use ($defaultValue) {
                    return property_exists($defaultValue->left, 'name') &&
                        $const->name === (string)$defaultValue->left->name;
                }
            );
            $constant = array_pop($constants);
            array_push($result, $preserveConstantNamesInsteadOfValues ? $constant->name : $constant->value);
        }
        if ($defaultValue->right instanceof ConstFetch) {
            $constants = array_filter(
                PhpStormStubsSingleton::getPhpStormStubs()->getConstants(),
                function (PHPConst $const) use ($defaultValue) {
                    return property_exists($defaultValue->right, 'name') &&
                        $const->name === (string)$defaultValue->right->name;
                }
            );
            $constant = array_pop($constants);
            array_push($result, $preserveConstantNamesInsteadOfValues ? $constant->name : $constant->value);
        }
        if ($defaultValue->left instanceof ClassConstFetch) {
            array_push($result, $this->getClassConstantDefaultValue(
                $defaultValue->left,
                $contextClass,
                $preserveConstantNamesInsteadOfValues
            ));
        }
        if ($defaultValue->right instanceof ClassConstFetch) {
            array_push($result, $this->getClassConstantDefaultValue(
                $defaultValue->right,
                $contextClass,
                $preserveConstantNamesInsteadOfValues
            ));
        }
    }

    /**
     * @param ClassConstFetch $defaultValue
     * @param PHPInterface|PHPClass|null $contextClass
     * @return bool|float|int|string|null
     * @throws RuntimeException
     * @throws \PHPUnit\Framework\Exception
     */
    public function getClassConstantDefaultValue(
        ClassConstFetch $defaultValue,
        PHPInterface|PHPClass|null $contextClass,
        $preserveConstantNamesInsteadOfValues = false
    ): string|int|bool|null|float
    {
        $class = (string)$defaultValue->class;
        if ($class === 'self' && $contextClass !== null) {
            $class = $contextClass->name;
        }
        if (PhpStormStubsSingleton::getPhpStormStubs()->getClass($class, shouldSuitCurrentPhpVersion: $this->shouldSuitCurrentPhpVersion) !== null) {
            $parentClass = PhpStormStubsSingleton::getPhpStormStubs()->getClass($class, shouldSuitCurrentPhpVersion: $this->shouldSuitCurrentPhpVersion);
        } else {
            $parentClass = PhpStormStubsSingleton::getPhpStormStubs()->getInterface($class, shouldSuitCurrentPhpVersion: $this->shouldSuitCurrentPhpVersion);
        }
        if ($parentClass === null) {
            throw new \PHPUnit\Framework\Exception("Class $class not found in stubs");
        }
        if ((string)$defaultValue->name === 'class') {
            $value = (string)$defaultValue->class;
        } else {
            $constant = $parentClass->getConstant((string)$defaultValue->name);
            $value = $preserveConstantNamesInsteadOfValues ? $parentClass->name . "::" . $constant->name : $constant->value;
        }
        return $value;
    }

    /**
     * @param ClassMethod $node
     * @param int $index
     * @throws RuntimeException
     */
    public function parseParameters(FunctionLike $node, $instance)
    {
        $index = 0;
        foreach ($node->getParams() as $parameter) {
            $parsedParameter = (new PHPParameter($this->shouldSuitCurrentPhpVersion))->readObjectFromStubNode($parameter);
            $key = $parsedParameter->name;
            if ($this->shouldSuitCurrentPhpVersion && BasePHPElement::entitySuitsCurrentPhpVersion($parsedParameter)) {
                $parsedParameter->indexInSignature = $index;
                $addedParameters = array_filter(
                    $instance->parameters,
                    function (PHPParameter $addedParameter) use ($parsedParameter) {
                        return $addedParameter->name === $parsedParameter->name;
                    }
                );
                if (!empty($addedParameters)) {
                    if ($parsedParameter->is_vararg) {
                        $parsedParameter->isOptional = false;
                        $index--;
                        $parsedParameter->indexInSignature = $index;
                    } else {
                        $key = $parsedParameter->name . '_duplicated';
                    }
                }
                $index++;
            } elseif (!$this->shouldSuitCurrentPhpVersion) {
                $addedParameters = array_filter(
                    $instance->parameters,
                    function (PHPParameter $addedParameter) use ($parsedParameter) {
                        return $addedParameter->name === $parsedParameter->name;
                    }
                );

                if (!empty($addedParameters)) {
                    $key = $parsedParameter->name . '_duplicated_' . sizeof($addedParameters);
                }
            }
            $instance->parameters[$key] = $parsedParameter;
        }
    }

    /**
     * @param ReflectionFunction|ReflectionFunctionAbstract $reflectionObject
     * @return static
     */
    public function readObjectFromReflection($reflectionObject)
    {
        $this->name = $reflectionObject->name;
        $this->isDeprecated = $reflectionObject->isDeprecated();
        foreach ($reflectionObject->getParameters() as $parameter) {
            $this->parameters[] = (new PHPParameter($this->shouldSuitCurrentPhpVersion))->readObjectFromReflection($parameter);
        }
        if (method_exists($reflectionObject, 'getReturnType')) {
            $returnTypes = self::getReflectionTypeAsArray($reflectionObject->getReturnType());
        }
        if (!empty($returnTypes)) {
            array_push($this->returnTypesFromSignature, ...$returnTypes);
        }
        return $this;
    }

    /**
     * @param Function_ $node
     * @return static
     * @throws RuntimeException
     */
    public function readObjectFromStubNode($node)
    {
        $functionName = self::getFQN($node);
        $this->name = $functionName;
        $this->attributes = $node->attrGroups;
        $typesFromAttribute = self::findTypesFromAttribute($node->attrGroups);
        $this->availableVersionsRangeFromAttribute = self::findAvailableVersionsRangeFromAttribute($node->attrGroups);
        $this->returnTypesFromAttribute = $typesFromAttribute;
        array_push($this->returnTypesFromSignature, ...self::convertParsedTypeToArray($node->getReturnType()));
        $this->parseParameters($node, $this);

        $this->collectTags($node);
        foreach ($this->parameters as $parameter) {
            $relatedParamTags = array_filter($this->paramTags, function (Param $tag) use ($parameter) {
                return $tag->getVariableName() === $parameter->name;
            });
            /** @var Param $relatedParamTag */
            $relatedParamTag = array_pop($relatedParamTags);
            if ($relatedParamTag !== null) {
                $parameter->isOptional = $parameter->isOptional || str_contains((string)$relatedParamTag->getDescription(), '[optional]');
                $parameter->markedOptionalInPhpDoc = str_contains((string)$relatedParamTag->getDescription(), '[optional]');
            }
        }

        $this->checkIfReturnTypeIsTentative($node);
        $this->checkDeprecationTag($node);
        $this->checkReturnTag();
        return $this;
    }

    protected function checkIfReturnTypeIsTentative(FunctionLike $node) {
        $this->hasTentativeReturnType = self::hasTentativeReturnTypeAttribute($node);
    }

    protected function checkDeprecationTag(FunctionLike $node)
    {
        $this->isDeprecated = self::hasDeprecatedAttribute($node) || !empty($this->deprecatedTags);
    }

    protected function checkReturnTag()
    {
        if (!empty($this->returnTags) && $this->returnTags[0] instanceof Return_) {
            $type = $this->returnTags[0]->getType();
            if ($type instanceof Collection) {
                $returnType = $type->getFqsen();
            } elseif ($type instanceof Array_ && $type->getValueType() instanceof Collection) {
                $returnType = "array";
            } else {
                $returnType = $type;
            }
            if ($returnType instanceof Compound) {
                foreach ($returnType as $nextType) {
                    $this->returnTypesFromPhpDoc[] = (string)$nextType;
                }
            } else {
                $this->returnTypesFromPhpDoc[] = (string)$returnType;
            }
        }
    }

    /**
     * @param stdClass|array $jsonData
     * @throws Exception
     */
    public function readMutedProblems($jsonData)
    {
        foreach ($jsonData as $function) {
            if ($function->name === $this->name) {
                if (!empty($function->problems)) {
                    foreach ($function->problems as $problem) {
                        switch ($problem->description) {
                            case 'parameter mismatch':
                                $this->mutedProblems[StubProblemType::FUNCTION_PARAMETER_MISMATCH] = $problem->versions;
                                break;
                            case 'missing function':
                                $this->mutedProblems[StubProblemType::STUB_IS_MISSED] = $problem->versions;
                                break;
                            case 'deprecated function':
                                $this->mutedProblems[StubProblemType::FUNCTION_IS_DEPRECATED] = $problem->versions;
                                break;
                            case 'absent in meta':
                                $this->mutedProblems[StubProblemType::ABSENT_IN_META] = $problem->versions;
                                break;
                            case 'has return typehint':
                                $this->mutedProblems[StubProblemType::FUNCTION_HAS_RETURN_TYPEHINT] = $problem->versions;
                                break;
                            case 'wrong return typehint':
                                $this->mutedProblems[StubProblemType::WRONG_RETURN_TYPEHINT] = $problem->versions;
                                break;
                            case 'has duplicate in stubs':
                                $this->mutedProblems[StubProblemType::HAS_DUPLICATION] = $problem->versions;
                                break;
                            case 'has type mismatch in signature and phpdoc':
                                $this->mutedProblems[StubProblemType::TYPE_IN_PHPDOC_DIFFERS_FROM_SIGNATURE] = $problem->versions;
                                break;
                            default:
                                throw new Exception("Unexpected value $problem->description");
                        }
                    }
                }
                if (!empty($function->parameters)) {
                    foreach ($this->parameters as $parameter) {
                        $parameter->readMutedProblems($function->parameters);
                    }
                }
            }
        }
    }

    /**
     * @param FunctionLike $node
     * @return bool
     */
    private static function hasDeprecatedAttribute(FunctionLike $node)
    {
        foreach ($node->getAttrGroups() as $group) {
            foreach ($group->attrs as $attr) {
                if ((string)$attr->name === Deprecated::class) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param FunctionLike $node
     * @return bool
     */
    public static function hasTentativeReturnTypeAttribute(FunctionLike $node)
    {
        foreach ($node->getAttrGroups() as $group) {
            foreach ($group->attrs as $attr) {
                if ((string)$attr->name === TentativeType::class) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param Doc|null $docComment
     * @return bool
     */
    private static function hasDeprecatedDocTag($docComment)
    {
        $phpDoc = $docComment !== null ? DocFactoryProvider::getDocFactory()->create($docComment->getText()) : null;
        return $phpDoc !== null && !empty($phpDoc->getTagsByName('deprecated'));
    }
}
