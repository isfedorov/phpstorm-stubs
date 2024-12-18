<?php

namespace StubTests\Parsers\Helpers;

use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\Internal\PhpStormStubsElementAvailable;
use JetBrains\PhpStorm\Internal\TentativeType;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use StubTests\Model\PhpVersions;

class AttributesHelper
{

    public static function hasTentativeTypeAttribute(array $attrGroups): bool
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
     * @param AttributeGroup[] $attrGroups
     *
     * @return array
     */
    public static function findAvailableVersionsRangeFromAttribute(array $attrGroups)
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
     * @return bool
     */
    public static function hasDeprecatedAttribute($node)
    {
        if (method_exists($node, 'getAttrGroups')) {
            foreach ($node->getAttrGroups() as $group) {
                foreach ($group->attrs as $attr) {
                    if ((string)$attr->name === Deprecated::class) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param mixed $node
     * @return bool
     */
    public static function deprecatedAttributeSuitsCurrentPhpVersion(mixed $node): bool
    {
        foreach ($node->getAttrGroups() as $group) {
            foreach ($group->attrs as $attr) {
                if ((string)$attr->name === Deprecated::class) {
                    foreach ($attr->args as $arg) {
                        if ($arg->name == 'since') {
                            return (float)$arg->value->value <= (float)getenv('PHP_VERSION');
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $node
     * @return bool
     */
    public static function isDeprecatedByAttribute($node): bool
    {
        return AttributesHelper::hasDeprecatedAttribute($node) && AttributesHelper::deprecatedAttributeSuitsCurrentPhpVersion($node);
    }
}