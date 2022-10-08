<?php

namespace StubTests\Model;

use Exception;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use JetBrains\PhpStorm\Internal\PhpStormStubsElementAvailable;
use JetBrains\PhpStorm\Internal\TentativeType;
use phpDocumentor\Reflection\Type;
use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\UnionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Reflector;
use RuntimeException;
use stdClass;
use StubTests\Parsers\ParserUtils;
use function array_key_exists;
use function count;
use function in_array;
use function number_format;

abstract class BasePHPElement
{
    use PHPDocElement;

    /** @var string|null */
    public $name;
    public $stubBelongsToCore = false;

    /** @var Exception|null */
    public $parseError;
    public $mutedProblems = [];
    public $availableVersionsRangeFromAttribute = [];

    /** @var string|null */
    public $sourceFilePath;

    /** @var string|null */
    public $sourceFileName;

    /** @var bool */
    public $duplicateOtherElement = false;

    /** @var array */
    public $attributes = [];

    /**
     * @var bool
     */
    public $isDeprecated;
    public $shouldSuitCurrentPhpVersion;

    /**
     * @param $shouldSuitCurrentPhpVersion
     */
    public function __construct($shouldSuitCurrentPhpVersion)
    {
        $this->shouldSuitCurrentPhpVersion = $shouldSuitCurrentPhpVersion;
    }

    /**
     * @param Reflector $reflectionObject
     * @return static
     */
    abstract public function readObjectFromReflection($reflectionObject);

    /**
     * @param Node $node
     * @return static
     */
    abstract public function readObjectFromStubNode($node);

    /**
     * @param stdClass|array $jsonData
     */
    abstract public function readMutedProblems($jsonData);

    /**
     * @param Node $node
     * @return string
     */
    public static function getFQN(Node $node)
    {
        $fqn = '';
        if (!property_exists($node, 'namespacedName') || $node->namespacedName === null) {
            if (property_exists($node, 'name')) {
                $fqn = $node->name;
            } else {
                foreach ($node->parts as $part) {
                    $fqn .= "$part\\";
                }
            }
        } else {
            return (string)$node->namespacedName;
        }
        return rtrim($fqn, "\\");
    }

    /**
     * @param ReflectionType|null $type
     * @return array
     */
    protected static function getReflectionTypeAsArray($type)
    {
        $reflectionTypes = [];
        if ($type instanceof ReflectionNamedType) {
            $type->allowsNull() && $type->getName() !== 'mixed' ?
                array_push($reflectionTypes, '?' . $type->getName()) : array_push($reflectionTypes, $type->getName());
        }
        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $namedType) {
                $reflectionTypes[] = $namedType->getName();
            }
        }
        return $reflectionTypes;
    }

    /**
     * @param Name|Identifier|NullableType|string|UnionType|null|Type $type
     * @return array
     */
    protected static function convertParsedTypeToArray($type)
    {
        $types = [];
        if ($type !== null) {
            if ($type instanceof UnionType) {
                foreach ($type->types as $namedType) {
                    $types[] = self::getTypeNameFromNode($namedType);
                }
            } elseif ($type instanceof Type) {
                array_push($types, ...explode('|', (string)$type));
            } else {
                $types[] = self::getTypeNameFromNode($type);
            }
        }
        return $types;
    }

    /**
     * @param Name|Identifier|NullableType|string $type
     * @return string
     */
    protected static function getTypeNameFromNode($type)
    {
        $nullable = false;
        $typeName = '';
        if ($type instanceof NullableType) {
            $type = $type->type;
            $nullable = true;
        }
        if (empty($type->name)) {
            if (!empty($type->parts)) {
                $typeName = $nullable ? '?' . implode('\\', $type->parts) : implode('\\', $type->parts);
            }
        } else {
            $typeName = $nullable ? '?' . $type->name : $type->name;
        }
        return $typeName;
    }

    /**
     * @param AttributeGroup[] $attrGroups
     * @return string[]
     */
    protected static function findTypesFromAttribute(array $attrGroups)
    {
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() === LanguageLevelTypeAware::class) {
                    $types = [];
                    $versionTypesMap = $attr->args[0]->value->items;
                    usort($versionTypesMap, function ($item1, $item2) {
                        return (float)$item1->key->value < (float)$item2->key->value ? -1 : 1;
                    });
                    foreach ($versionTypesMap as $item) {
                        $phpVersion = number_format((float)$item->key->value, 1);
                        $types[$phpVersion] =
                            explode('|', preg_replace('/\w+\[]/', 'array', $item->value->value));
                        foreach (new PhpVersions() as $version) {
                            if ($version > (float)$phpVersion) {
                                $types[number_format($version, 1)] = $types[$phpVersion];
                            }
                        }
                    }
                    $types[$attr->args[1]->name->name] = explode('|', preg_replace('/\w+\[]/', 'array', $attr->args[1]->value->value));
                    return $types;
                }
            }
        }
        return [];
    }

    /**
     * @param AttributeGroup[] $attrGroups
     * @return array
     */
    protected static function findAvailableVersionsRangeFromAttribute(array $attrGroups)
    {
        $versionRange = [];
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() === PhpStormStubsElementAvailable::class) {
                    if (count($attr->args) === 2) {
                        foreach ($attr->args as $arg) {
                            $versionRange[$arg->name->name] = (float)$arg->value->value;
                        }
                    } else {
                        $arg = $attr->args[0]->value;
                        if ($arg instanceof Array_) {
                            $value = $arg->items[0]->value;
                            if ($value instanceof String_) {
                                return ['from' => (float)$value->value];
                            }
                        } else {
                            $rangeName = $attr->args[0]->name;
                            return $rangeName === null || $rangeName->name === 'from' ?
                                ['from' => (float)$arg->value, 'to' => PhpVersions::getLatest()] :
                                ['from' => PhpVersions::getFirst(), 'to' => (float)$arg->value];
                        }
                    }
                }
            }
        }
        return $versionRange;
    }

    /**
     * @param array $attrGroups
     * @return bool
     */
    protected static function hasTentativeTypeAttribute(array $attrGroups)
    {
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() === TentativeType::class) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param int $stubProblemType
     * @return bool
     */
    public function hasMutedProblem($stubProblemType)
    {
        if (array_key_exists($stubProblemType, $this->mutedProblems)) {
            if (in_array('ALL', $this->mutedProblems[$stubProblemType], true) ||
                in_array((float)getenv('PHP_VERSION'), $this->mutedProblems[$stubProblemType], true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param BasePHPElement $element
     * @return bool
     * @throws RuntimeException
     */
    public static function entitySuitsCurrentPhpVersion(BasePHPElement $element)
    {
        return in_array((float)getenv('PHP_VERSION'), ParserUtils::getAvailableInVersions($element), true);
    }
}
