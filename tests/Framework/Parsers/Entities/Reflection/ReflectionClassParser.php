<?php

namespace StubTests\Sources\Parsers\Entities\Reflection;

use ReflectionClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionClass;
use StubTests\Sources\Parsers\Parser;

/**
 * @template-implements Parser<AdaptedReflectionClass>
 */
class ReflectionClassParser implements Parser
{
    private ReflectionMethodParser $methodParser;
    private ReflectionPropertyParser $propertyParser;
    private ReflectionClassConstantParser $constantParser;
    private ReflectionParentClassParser $parentClassParser;
    private ReflectionImplementedInterfaceParser $interfaceParser;

    public function __construct(
        ?ReflectionMethodParser $methodParser = null,
        ?ReflectionPropertyParser $propertyParser = null,
        ?ReflectionClassConstantParser $constantParser = null,
        ?ReflectionParentClassParser $parentClassParser = null,
        ?ReflectionImplementedInterfaceParser $interfaceParser = null
    ) {
        $this->methodParser = $methodParser ?? new ReflectionMethodParser();
        $this->propertyParser = $propertyParser ?? new ReflectionPropertyParser();
        $this->constantParser = $constantParser ?? new ReflectionClassConstantParser();
        $this->parentClassParser = $parentClassParser ?? new ReflectionParentClassParser();
        $this->interfaceParser = $interfaceParser ?? new ReflectionImplementedInterfaceParser();
    }

    public function canParse($object): bool
    {
        return $object instanceof AdaptedReflectionClass
            && $object->isInternal()
            && !$object->isInterface()
            && !$object->isEnum();
    }

    /**
     * Parse an AdaptedReflectionClass into a PHPClass model
     *
     * @param AdaptedReflectionClass $object
     * @return PHPClass
     */
    public function parse($object): PHPClass
    {
        $class = new PhpClass();
        $class->setName(!empty($object->getShortName()) ? $object->getShortName() : null);
        $class->setNamespace($object->getNamespaceName() ? '\\' . $object->getNamespaceName() : '\\');
        if ($class->getName()) {
            $class->setId($class->getNamespace() != '\\' ? $class->getNamespace() . '\\' . $class->getName() : '\\' . $class->getName());
        }
        $class->isFinal = $object->isFinal();
        $class->isReadonly = $object->isReadOnly();
        foreach ($object->getMethods() ?? [] as $method) {
            $class->methods []= $this->methodParser->parse($method);
        }
        foreach ($object->getProperties() ?? [] as $property) {
            $class->properties []= $this->propertyParser->parse($property);
        }
        if ($object->hasMethod('getReflectionConstants')) {
            foreach ($object->getReflectionConstants() ?? [] as $reflectionConstant) {
                $class->constants []= $this->constantParser->parse($reflectionConstant);
            }
        } else {
            foreach ($object->getConstants() ?? [] as $constantName => $constantValue) {
                $class->constants []= $this->constantParser->parse([$constantName => $constantValue]);
            }
        }
        if ($object->getParentClass()) {
            $class->parentClass = $this->parentClassParser->parse($object->getParentClass());
        }
        if ($object->getInterfaces()) {
            foreach ($object->getInterfaces() ?? [] as $interface) {
                $class->interfaces []= $this->interfaceParser->parse($interface);
            }
        }
        return $class;
    }
}