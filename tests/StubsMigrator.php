<?php

namespace StubTests;

require 'vendor/autoload.php';

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Internal\ReturnTypeContract;
use JetBrains\PhpStorm\Internal\TentativeType;
use JetBrains\PhpStorm\Language;
use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\UnaryMinus;
use StubTests\Model\BasePHPClass;
use StubTests\Model\BasePHPElement;
use StubTests\Model\PHPClass;
use StubTests\Model\PHPConst;
use StubTests\Model\PHPFunction;
use StubTests\Model\PHPInterface;
use StubTests\Model\PHPMethod;
use StubTests\Model\PHPParameter;
use StubTests\Model\PHPProperty;
use StubTests\Parsers\ParserUtils;
use StubTests\TestData\Providers\PhpStormStubsSingleton;
use function array_filter;
use function array_key_exists;
use function array_map;
use function basename;
use function dirname;
use function file_exists;
use function file_put_contents;
use function implode;
use function in_array;
use function is_dir;
use function is_int;
use function is_string;
use function mkdir;
use function number_format;
use function property_exists;
use function realpath;
use function str_replace;
use function strval;
use function trim;
use function var_dump;
use const FILE_APPEND;

class StubsMigrator
{
    public static function migrateStubs()
    {
        $allStubs = PhpStormStubsSingleton::getPhpStormStubs(false);
        foreach ($allStubs->getCoreConstants() as $constant) {
            $versions = ParserUtils::getAvailableInVersions($constant);
            foreach ($versions as $version) {
                self::moveToVersionedDir($constant, $version);
            }
        }
        foreach ($allStubs->getCoreFunctions() as $function) {
            $versions = ParserUtils::getAvailableInVersions($function);
            foreach ($versions as $version) {
                self::moveToVersionedDir($function, $version);
            }
        }

        foreach ($allStubs->getCoreInterfaces() as $interface) {
            $versions = ParserUtils::getAvailableInVersions($interface);
            foreach ($versions as $version) {
                self::moveToVersionedDir($interface, $version);
            }
        }

        foreach ($allStubs->getCoreClasses() as $class) {
            $versions = ParserUtils::getAvailableInVersions($class);
            foreach ($versions as $version) {
                self::moveToVersionedDir($class, $version);
            }
        }
    }

    private static function moveToVersionedDir($element, $version)
    {
        $folderName = basename($element->sourceFilePath);
        $fileName = $element->sourceFileName;
        $folderLabel = str_replace(".", "", $version);
        $newDirectory = realpath(dirname(__FILE__)) . "/../PHP_$folderLabel/$folderName";
        if (!is_dir($newDirectory)) {
            mkdir($newDirectory, recursive: true);
        }
        $newFile = "$newDirectory/$fileName";
        if (!file_exists($newFile)) {
            file_put_contents($newFile, "<?php\n");
        }
        if ($element instanceof PHPFunction) {
            self::writeFunction($element, $newFile, $version);
        }
        if ($element instanceof PHPConst) {
            self::writeConstant($element, $newFile, $version);
        }
        if ($element instanceof PHPInterface) {
            self::writeInterface($element, $newFile, $version);
        }
        if ($element instanceof PHPClass) {
            self::writeClass($element, $newFile, $version);
        }
    }

    private static function writeFunction(PHPFunction $element, $file, $version)
    {
        $parameters = self::convertParametersToString($element, $version);
        $attributes = implode("\n", self::getAttributesAsStrings($element->attributes, $version));
        $returnType = implode('|', $element->returnTypesFromSignature);;
        $typesFromAttribute = $element->returnTypesFromAttribute;
        if (!empty($typesFromAttribute)) {
            if (array_key_exists(number_format($version, 1), $typesFromAttribute)) {
                $typesFromAttributes = implode('|', $typesFromAttribute[number_format($version, 1)]);
            } else {
                $typesFromAttributes = implode('|', $typesFromAttribute['default']);
            }
            if (!empty($typesFromAttributes)) {
                $returnType = "$typesFromAttributes";
            }
        }
        if (!empty($returnType)) {
            $returnType = ": $returnType";
        }
        $template = <<<EOF

{$element->phpdoc}
{$attributes}
function {$element->name}($parameters)$returnType {}

EOF;

        file_put_contents($file, $template, FILE_APPEND);
    }

    private static function writeConstant(PHPConst $element, $file, $version)
    {
        $value = $element->value;
        $attributes = implode("\n", self::getAttributesAsStrings($element->attributes, $version));
        if (is_string($element->value)) {
            $value = "\"$element->value\"";
        }
        $template = <<<EOF

{$element->phpdoc}{$attributes}
define('{$element->name}', {$value});

EOF;

        file_put_contents($file, $template, FILE_APPEND);
    }

    private static function writeInterface(PHPInterface $element, $file, $version)
    {
        $extendsBlock = "";
        $parentInterfaces = self::getExtendedInterfacesAsString($element, $version);
        if (!empty($parentInterfaces)) {
            $extendsBlock = " extends $parentInterfaces";
        }
        $constants = self::getAllConstantsOfClassAsString($element, $version);
        $methods = self::getAllMethodsOfClassAsString($element, $version);
        $attributes = implode("\n", self::getAttributesAsStrings($element->attributes, $version));
        $template = <<<EOF
{$element->phpdoc}
{$attributes}
interface {$element->name}{$extendsBlock}{
$constants
$methods
}

EOF;

        file_put_contents($file, $template, FILE_APPEND);
    }

    private static function writeClass(PHPClass $element, $file, $version)
    {
        $extendsBlock = '';
        $implementsBlock = '';
        $isFinal = "";
        $isReadonly = '';
        if ($element->isFinal) {
            $isFinal = 'final';
        }
        if ($element->isReadonly) {
            $isReadonly = 'readonly';
        }
        $parentClass = $element->parentClass;
        if (!empty($parentClass)) {
            $extendsBlock = " extends $parentClass";
        }
        $parentInterfaces = implode(",", array_map(fn (PHPInterface $interface) => $interface->name, $element->interfaces));
        if (!empty($parentInterfaces)) {
            $implementsBlock = " implements $parentInterfaces";
        }
        $constants = self::getAllConstantsOfClassAsString($element, $version);
        $properties = self::getAllPropertiesOfClassAsString($element, $version);
        $methods = self::getAllMethodsOfClassAsString($element, $version);
        $attributes = implode("\n", self::getAttributesAsStrings($element->attributes, $version));
        $template = <<<EOF
{$element->phpdoc}
{$attributes}
{$isFinal} {$isReadonly} class {$element->name}{$extendsBlock}{$implementsBlock}{
$constants
$properties
$methods
}

EOF;

        file_put_contents($file, $template, FILE_APPEND);
    }

    private static function getExtendedInterfacesAsString(PHPInterface $interface, $version): string
    {
        if (!empty($interface->parentInterfaces)) {
            if (in_array(null, $interface->parentInterfaces)) {
                var_dump($interface->parentInterfaces);
            }
            return implode(',', array_map(fn (PHPInterface $in) => $in->name, $interface->parentInterfaces));
        }
        return "";
    }

    private static function getAllPropertiesOfClassAsString(PHPClass $class, $version)
    {
        $props = $class->properties;
        $properties = array_filter($props, function (PHPProperty $property) use ($version) {
            return in_array($version, ParserUtils::getAvailableInVersions($property));
        });
        $resulString = implode("\n", array_map(function (PHPProperty $property) use ($version) {
            $attributes = implode("\n", self::getAttributesAsStrings($property->attributes, $version));
            $isReadonly = '';
            $isStatic = '';
            $defaultValue = "";
            if (!empty($property->defaultValue)) {
                $defaultValue = "={$property->defaultValue}";
            }
            if ($property->isReadonly) {
                $isReadonly = 'readonly';
            }
            if ($property->isStatic) {
                $isStatic = 'static';
            }
            $resultType = implode('|', $property->typesFromSignature);
            $typesFromAttributes = $property->typesFromAttribute;
            if (!empty($typesFromAttributes)) {
                if (array_key_exists(number_format($version, 1), $typesFromAttributes)) {
                    $typesFromAttributes = implode('|', $typesFromAttributes[number_format($version, 1)]);
                } else {
                    $typesFromAttributes = implode('|', $typesFromAttributes['default']);
                }
                if (!empty($typesFromAttributes)) {
                    if (empty($resultType)) {
                        $resultType .= "$typesFromAttributes";
                    } else {
                        $resultType .= "|$typesFromAttributes";
                    }
                }
            }
            $result = <<<EOF
{$property->phpdoc}
{$attributes}
{$property->access} {$isStatic} {$isReadonly} {$resultType} \${$property->name}$defaultValue;\n
EOF;
            return $result;
        }, $properties));
        return $resulString;
    }

    private static function getAllConstantsOfClassAsString(BasePHPClass $interface, $version): string
    {
        $constants = array_filter($interface->constants, function (PHPConst $const) use ($version) {
            return in_array($version, ParserUtils::getAvailableInVersions($const));
        });
        $resulString = implode("\n", array_map(function (PHPConst $const) use ($version) {
            $attributes = implode("\n", self::getAttributesAsStrings($const->attributes, $version));
            $result = <<<EOF
{$const->phpdoc}
{$attributes}
{$const->visibility} const {$const->name}={$const->value};\n
EOF;
            return $result;
        }, $constants));
        return $resulString;
    }

    private static function getAllMethodsOfClassAsString(BasePHPClass $classLike, $version): string
    {
        $methods = "";
        if ($classLike instanceof PHPInterface) {
            $end = ";";
        } else {
            $end = "{}";
        }
        $availableMethods = array_filter($classLike->methods, function (PHPMethod $method) use ($version) {
            return in_array($version, ParserUtils::getAvailableInVersions($method));
        });
        foreach ($availableMethods as $method) {
            $isFinal = "";
            $isStatic = "";
            if ($method->isFinal) {
                $isFinal = "final";
            }
            if ($method->isStatic) {
                $isStatic = "static";
            }
            $parameters = self::convertParametersToString($method, $version, $classLike);
            $attributes = implode("\n", self::getAttributesAsStrings($method->attributes, $version));
            $returnType = implode('|', $method->returnTypesFromSignature);;
            $typesFromAttribute = $method->returnTypesFromAttribute;
            if (!empty($typesFromAttribute)) {
                if (array_key_exists(number_format($version, 1), $typesFromAttribute)) {
                    $typesFromAttributes = implode('|', $typesFromAttribute[number_format($version, 1)]);
                } else {
                    $typesFromAttributes = implode('|', $typesFromAttribute['default']);
                }
                if (!empty($typesFromAttributes)) {
                    $returnType = "$typesFromAttributes";
                }
            }
            if (!empty($returnType)) {
                $returnType = ": $returnType";
            }
            $template = <<<EOF
{$method->phpdoc}{$attributes}
{$method->access} {$isStatic} {$isFinal} function {$method->name}($parameters)$returnType{$end}

EOF;
            $methods .= $template . "\n";
        }
        return $methods;
    }

    private static function convertParametersToString(PHPFunction $element, $version, BasePHPClass $parentClass = null)
    {
        $result = "";
        $availableParameters = array_filter($element->parameters, function (PHPParameter $parameter) use ($version) {
            $availableInVersions = ParserUtils::getAvailableInVersions($parameter);
            return in_array($version, $availableInVersions);
        });
        foreach ($availableParameters as $parameter) {
            $result .= implode("\n", self::getAttributesAsStrings($parameter->attributes, $version));
            $resultType = implode("|", $parameter->typesFromSignature);
            $typesFromAttributes = $parameter->typesFromAttribute;
            if (!empty($typesFromAttributes)) {
                if (array_key_exists(number_format($version, 1), $typesFromAttributes)) {
                    $typesFromAttributes = implode('|', $typesFromAttributes[number_format($version, 1)]);
                } else {
                    $typesFromAttributes = implode('|', $typesFromAttributes['default']);
                }
                if (!empty($typesFromAttributes)) {
                    if (empty($resultType)) {
                        $resultType .= "$typesFromAttributes";
                    } else {
                        $resultType = "$typesFromAttributes";
                    }
                }
            }
            $isReference = $parameter->is_passed_by_ref;

            $result .= $isReference ? "$resultType &$$parameter->name" : "$resultType $$parameter->name";
            if (!empty($parameter->defaultValue)) {
                if ($parameter->defaultValue instanceof ConstFetch) {
                    $default = (string)$parameter->defaultValue->name;
                } else {
                    $default = PHPFunction::getStringRepresentationOfDefaultParameterValue(
                        $parameter->defaultValue,
                        $parentClass,
                        preserveConstantNamesInsteadOfValues: true
                    );
                }
                if (!is_int($default) && empty($default)) {
                    $default = "\"\"";
                }
                $result .= " = $default,";
            } else {
                $result .= ",";
            }
        }
        return trim($result, ", ");
    }

    public static function getAttributesAsStrings(array $attrGroups, $version)
    {
        $attributesOfNode = [];

        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                //get only attributes that fit $version
                if ($attr->name->toString() === ReturnTypeContract::class) {
                    $result = '#[\JetBrains\PhpStorm\Internal\ReturnTypeContract(';
                    $values = self::getReturnTypeContractValues($attr->args);
                    $result .= "$values)]";
                    $attributesOfNode[ReturnTypeContract::class] = $result;
                }
                if ($attr->name->toString() === TentativeType::class) {
                    $attributesOfNode[TentativeType::class] = '#[\JetBrains\PhpStorm\Internal\TentativeType]';
                }
                if ($attr->name->toString() === ArrayShape::class) {
                    $result = '#[\JetBrains\PhpStorm\ArrayShape([';
                    $arrayShape = self::getArrayShape($attr->args);
                    $result .= "$arrayShape])]";
                    $attributesOfNode[ArrayShape::class] = $result;
                }
                if ($attr->name->toString() === Deprecated::class) {
                    $result = '#[\JetBrains\PhpStorm\Deprecated';
                    $deprecatedParameters = self::getDeprecatedParameters($attr->args);
                    if (!empty($deprecatedParameters)) {
                        $result .= "($deprecatedParameters)";
                    }
                    $result .= ']';
                    $attributesOfNode[Deprecated::class] = $result;
                }
                if ($attr->name->toString() === ExpectedValues::class) {
                    $result = '#[\JetBrains\PhpStorm\ExpectedValues([';
                    $expectedValues = self::getExpectedValues($attr->args);
                    $result .= "$expectedValues])]";
                    $attributesOfNode[ExpectedValues::class] = $result;
                }
                if ($attr->name->toString() === Immutable::class) {
                    $result = '#[\JetBrains\PhpStorm\Immutable';
                    $allowedWriteScope = self::getAllowedWriteScope($attr->args);
                    if (!empty($allowedWriteScope)) {
                        $result .= "($allowedWriteScope)";
                    }
                    $result .= ']';
                    $attributesOfNode[Immutable::class] = $result;
                }
                if ($attr->name->toString() === Language::class) {
                    $result = '#[\JetBrains\PhpStorm\Language("';
                    $language = self::getLanguageFromAttribute($attr->args);
                    $result .= "$language\")]";
                    $attributesOfNode[Language::class] = $result;
                }
                if ($attr->name->toString() === NoReturn::class) {
                    $attributesOfNode[NoReturn::class] = '#[\JetBrains\PhpStorm\NoReturn]';
                }
                if ($attr->name->toString() === Pure::class) {
                    $result = '#[\JetBrains\PhpStorm\Pure';
                    $dependsOnGlobalScope = self::isPureAttributeDependingOnGlobalScope($attr->args);
                    if ($dependsOnGlobalScope) {
                        $result .= '(true)';
                    }
                    $result .= ']';
                    $attributesOfNode[Pure::class] = $result;
                }
            }
        }
        return $attributesOfNode;
    }

    private static function isPureAttributeDependingOnGlobalScope($args): bool
    {
        if (!empty($args)) {
            return $args[0]->value->name->parts[0];
        }
        return false;
    }

    private static function getDeprecatedParameters($args): string
    {
        $parameters = '';
        foreach ($args as $arg) {
            if (!empty($arg->name)) {
                $key = "{$arg->name->name}: ";
            } else {
                $key = '';
            }
            $value = $arg->value->value;
            $parameters .= "$key\"$value\", ";
        }
        return trim($parameters, ', ');
    }

    private static function getExpectedValues($args): string
    {
        $parameters = '';
        $value = '';
        if (property_exists($args[0]->value, "items")) {
            foreach ($args[0]->value->items as $item) {
                if ($item->value instanceof UnaryMinus) {
                    $value = "-" . $item->value->expr->value;
                } else {
                    $value = $item->value->name ?? strval($item->value->value);
                }
                $parameters .= $value . ', ';
            }
        } else {
            $value = "\\{$args[0]->value->class}::{$args[0]->value->name}";
            $parameters .= $value . ', ';
        }
        return trim($parameters, ', ');
    }

    private static function getArrayShape($args): string
    {
        $parameters = '';
        foreach ($args[0]->value->items as $item) {
            $key = $item->key->value;
            $value = $item->value->value;
            $parameters .= "\"$key\" => \"$value\"" . ', ';
        }
        return trim($parameters, ', ');
    }

    private static function getReturnTypeContractValues($args): string
    {
        $parameters = '';
        foreach ($args as $arg) {
            $key = $arg->name->name;
            $value = $arg->value->value;
            $parameters .= "$key: \"$value\"" . ', ';
        }
        return trim($parameters, ', ');
    }

    private static function getLanguageFromAttribute($args): string
    {
        return $args[0]->value->value;
    }

    private static function getAllowedWriteScope($args): string
    {
        if (!empty($args)) {
            $class = BasePHPElement::getFQN($args[0]->value->class);
            $constant = $args[0]->value->name->name;
            return "\\$class::$constant";
        }
        return "";
    }
}

StubsMigrator::migrateStubs();
