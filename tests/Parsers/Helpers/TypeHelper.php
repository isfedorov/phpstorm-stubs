<?php

namespace StubTests\Parsers\Helpers;

use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use phpDocumentor\Reflection\Type;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use StubTests\Model\PhpVersions;

class TypeHelper
{

    /**
     * @param ReflectionNamedType $type
     * @return bool
     */
    public static function typeIsNullable(ReflectionNamedType $type): bool
    {
        return $type->allowsNull() && $type->getName() !== 'mixed';
    }

    /**
     * @param ReflectionType|null $type
     *
     * @return array
     */
    public static function getReflectionTypeAsArray($type)
    {
        $reflectionTypes = [];
        if ($type instanceof ReflectionNamedType) {
            TypeHelper::typeIsNullable($type) ?
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
     * @param Name|Identifier|NullableType|string $type
     * @return string
     */
    public static function getTypeNameFromNode($type)
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
     * @param Name|Identifier|NullableType|string|UnionType|null|Type $type
     *
     * @return array
     */
    public static function convertParsedTypeToArray($type)
    {
        $types = [];
        if ($type !== null) {
            if ($type instanceof UnionType) {
                foreach ($type->types as $namedType) {
                    $types[] = TypeHelper::getTypeNameFromNode($namedType);
                }
            } elseif ($type instanceof Type) {
                array_push($types, ...explode('|', (string)$type));
            } else {
                $types[] = TypeHelper::getTypeNameFromNode($type);
            }
        }

        return $types;
    }

    /**
     * @param AttributeGroup[] $attrGroups
     *
     * @return string[]
     */
    public static function findTypesFromAttribute(array $attrGroups)
    {
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() === LanguageLevelTypeAware::class) {
                    $types = [];
                    $versionTypesMap = $attr->args[0]->value->items;
                    $defaultType = explode('|', preg_replace('/\w+\[]/', 'array', $attr->args[1]->value->value));

                    // Collecting explicit types from the attribute.
                    foreach ($versionTypesMap as $item) {
                        $types[number_format((float)$item->key->value, 1)] =
                            explode('|', preg_replace('/\w+\[]/', 'array', $item->value->value));
                    }

                    // Populate the results for all required PHP versions.
                    $result = [];
                    foreach (new PhpVersions() as $version) {
                        $versionKey = number_format($version, 1);

                        // Find the appropriate type for the current version.
                        if (isset($types[$versionKey])) {
                            $result[$versionKey] = $types[$versionKey];
                        } else {
                            // Look for the closest lower or equal version.
                            $closestType = $defaultType;
                            foreach ($types as $typeVersion => $typeValue) {
                                if (floatval($versionKey) >= floatval($typeVersion)) {
                                    $closestType = $typeValue;
                                } else {
                                    break;
                                }
                            }
                            $result[$versionKey] = $closestType;
                        }
                    }

                    return $result;
                }
            }
        }

        return [];
    }
}