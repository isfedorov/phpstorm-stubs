<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionFunction;
use StubTests\Sources\Model\Entities\NoType;
use StubTests\Sources\Model\Entities\NullableType;
use StubTests\Sources\Model\Entities\PHPFunction;
use StubTests\Sources\Model\Entities\PHPParameter;
use StubTests\Sources\Model\Entities\StandaloneType;
use StubTests\Sources\Model\Entities\UnionType;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<ReflectionFunction>
 */
class ReflectionFunctionParser implements Parser {

    public function canParseReflectionClass($object)
    {
        // TODO: Implement canParseReflectionClass() method.
    }

    public function parse($object)
    {
        $PHPFunction = new PHPFunction();
        $PHPFunction->setName(!empty($object->getName()) ? $object->getName() : null);
        $PHPFunction->setNamespace($object->getNamespaceName() ? '\\' . $object->getNamespaceName() : '\\');
        if (!$PHPFunction->getName()){
            $PHPFunction->setId(null);
        } elseif ($PHPFunction->getNamespace() === '\\') {
            $PHPFunction->setId('\\' . $PHPFunction->getName());
        } else {
            $PHPFunction->setId($PHPFunction->getNamespace() . '\\' . $PHPFunction->getName());
        }
        $returnTypesFromSignature = new NoType();
        if ($object->hasReturnType() && $object->getReturnType()) {
            $returnType = $object->getReturnType();

            // Use duck typing to check for union types (supports both real Reflection and wrappers)
            $isUnion = ($returnType instanceof \ReflectionUnionType) ||
                       (method_exists($returnType, 'isUnionType') && $returnType->isUnionType());
            $isIntersection = (class_exists('\ReflectionIntersectionType') && $returnType instanceof \ReflectionIntersectionType) ||
                             (method_exists($returnType, 'isIntersectionType') && $returnType->isIntersectionType());

            if ($isUnion) {
                $returnTypesFromSignature = new UnionType();
                foreach ($returnType->getTypes() as $item) {
                    if ($item instanceof \ReflectionNamedType || method_exists($item, 'getName')) {
                        $returnTypesFromSignature->addType(new StandaloneType($item->getName()));
                    }
                }
            } elseif($isIntersection) {
              foreach ($returnType->getTypes() as $item) {
                  $returnTypesFromSignature []= $item->getName();
              }
            } elseif($returnType->allowsNull()) {
                $typeName = $returnType->getName();
                if ($typeName !== null) {
                    $returnTypesFromSignature = new NullableType();
                    $returnTypesFromSignature->addBasicType(new StandaloneType($typeName));
                }
            } else {
                $typeName = $returnType->getName();
                if ($typeName !== null) {
                    $returnTypesFromSignature = new StandaloneType($typeName);
                }
            }
        }
        $PHPFunction->setReturnTypeFromSignature($returnTypesFromSignature);
        $PHPFunction->setDeprecated($object->isDeprecated());

        // Convert ReflectionParameter objects to PHPParameter objects
        $parameters = array_map(
            function($parameter) { return new PHPParameter($parameter->getName()); },
            $object->getParameters()
        );
        $PHPFunction->setParameters($parameters);

        return $PHPFunction;
    }
}