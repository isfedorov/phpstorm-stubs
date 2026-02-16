<?php

namespace StubTests\Sources\Parsers;

use StubTests\Framework\Parsers\Entities\Model\Access\PrivateAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\ProtectedAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\PublicAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;

/**
 * Serializer for stub entities that includes all stub-specific metadata:
 * - PhpDoc comments (raw text)
 * - Version information (sinceVersion, removedVersion)
 * - Type information from multiple sources (signature, PhpDoc, LanguageLevelTypeAware)
 * - LanguageLevelTypeAware attribute data (version-specific types)
 */
class StubsEntitySerializer implements EntitySerializerInterface
{
    /**
     * Convert value to JSON-safe format, filtering out resources and closures
     */
    private function toJsonSafe($value)
    {
        if (is_resource($value)) {
            return '[resource]';
        }

        if ($value instanceof \Closure) {
            return '[closure]';
        }

        if (is_object($value) && !($value instanceof \stdClass) && !($value instanceof \DateTimeInterface)) {
            // Skip complex objects that aren't basic types
            return '[object:' . get_class($value) . ']';
        }

        if (is_array($value)) {
            return array_map([$this, 'toJsonSafe'], $value);
        }

        return $value;
    }

    public function serialize($entity): array
    {
        if ($entity instanceof PHPClass) {
            return $this->serializeClass($entity);
        }

        if ($entity instanceof PHPFunction) {
            return $this->serializeFunction($entity);
        }

        if ($entity instanceof PHPInterface) {
            return $this->serializeInterface($entity);
        }

        if ($entity instanceof PHPEnum) {
            return $this->serializeEnum($entity);
        }

        if ($entity instanceof PHPConstant) {
            return $this->serializeConstant($entity);
        }

        // Fallback for unknown entity types
        return [
            '_type' => 'Unknown',
            'className' => get_class($entity),
            'data' => serialize($entity)
        ];
    }

    public function deserialize(array $data)
    {
        $type = $data['_type'] ?? 'Unknown';

        return match ($type) {
            'PHPClass' => $this->deserializeClass($data),
            'PHPFunction' => $this->deserializeFunction($data),
            'PHPInterface' => $this->deserializeInterface($data),
            'PHPEnum' => $this->deserializeEnum($data),
            'PHPConstant' => $this->deserializeConstant($data),
            default => isset($data['data']) ? unserialize($data['data']) : null,
        };
    }

    private function serializeClass(PHPClass $entity): array
    {
        $data = [
            '_type' => 'PHPClass',
            'name' => $this->toJsonSafe($entity->getName()),
            'id' => $this->toJsonSafe($entity->getId()),
            'isFinal' => $this->toJsonSafe($entity->isFinal ?? null),
            'isReadonly' => $this->toJsonSafe($entity->isReadonly ?? null),
            'sourcePath' => $this->toJsonSafe($entity->getSourcePath()),
            'duplicates' => $this->toJsonSafe($entity->getDuplicates()),
        ];

        // Safely get namespace
        try {
            $data['namespace'] = $this->toJsonSafe($entity->getNamespace());
        } catch (\Error $e) {
            $data['namespace'] = null;
        }

        // Stub-specific metadata
        $data['phpDoc'] = $this->toJsonSafe($entity->getPhpDoc());
        $data['sinceVersion'] = $this->toJsonSafe($entity->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($entity->getRemovedVersion());

        // Serialize methods
        $data['methods'] = [];
        foreach ($entity->methods as $method) {
            $data['methods'][] = $this->serializeMethod($method);
        }

        // Serialize properties
        $data['properties'] = [];
        foreach ($entity->properties as $property) {
            $data['properties'][] = $this->serializeProperty($property);
        }

        // Serialize constants
        $data['constants'] = [];
        foreach ($entity->constants as $constant) {
            $data['constants'][] = $this->serializeClassConstant($constant);
        }

        // Serialize parent class (just store the name)
        if ($entity->parentClass !== null) {
            $data['parentClass'] = $entity->parentClass->getName();
        } else {
            $data['parentClass'] = null;
        }

        // Serialize interfaces (just store the names)
        $data['interfaces'] = [];
        foreach ($entity->interfaces as $interface) {
            $data['interfaces'][] = $interface->getName();
        }

        return $data;
    }

    private function serializeFunction(PHPFunction $entity): array
    {
        $data = [
            '_type' => 'PHPFunction',
            'name' => $this->toJsonSafe($entity->getName()),
            'id' => $this->toJsonSafe($entity->getId()),
            'sourcePath' => $this->toJsonSafe($entity->getSourcePath()),
            'duplicates' => $this->toJsonSafe($entity->getDuplicates()),
        ];

        try {
            $data['namespace'] = $this->toJsonSafe($entity->getNamespace());
        } catch (\Error $e) {
            $data['namespace'] = null;
        }

        try {
            $data['isDeprecated'] = $this->toJsonSafe($entity->isDeprecated());
        } catch (\Error $e) {
            $data['isDeprecated'] = false;
        }

        try {
            // Serialize return type (always a type object)
            $returnType = $entity->getReturnTypeFromSignature();
            $data['returnType'] = $returnType?->toString() ?? null;
            $data['hasTentativeReturnType'] = $this->toJsonSafe($entity->hasTentativeReturnType());
        } catch (\Error $e) {
            $data['returnType'] = null;
            $data['hasTentativeReturnType'] = false;
        }

        // Stub-specific metadata
        $data['phpDoc'] = $this->toJsonSafe($entity->getPhpDoc());
        $data['sinceVersion'] = $this->toJsonSafe($entity->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($entity->getRemovedVersion());
        $data['returnTypeFromPhpDoc'] = $this->toJsonSafe($entity->getReturnTypeFromPhpDoc());
        $data['languageLevelTypes'] = $this->toJsonSafe($entity->getLanguageLevelTypes());
        $data['defaultType'] = $this->toJsonSafe($entity->getDefaultType());

        // Serialize parameters
        try {
            $parameters = $entity->getParameters();
            $data['parameters'] = [];
            foreach ($parameters ?? [] as $param) {
                $data['parameters'][] = $this->serializeParameter($param);
            }
        } catch (\Error $e) {
            $data['parameters'] = [];
        }

        return $data;
    }

    private function serializeInterface(PHPInterface $entity): array
    {
        $data = [
            '_type' => 'PHPInterface',
            'name' => $this->toJsonSafe($entity->getName()),
            'id' => $this->toJsonSafe($entity->getId()),
            'sourcePath' => $this->toJsonSafe($entity->getSourcePath()),
            'duplicates' => $this->toJsonSafe($entity->getDuplicates()),
        ];

        try {
            $data['namespace'] = $this->toJsonSafe($entity->getNamespace());
        } catch (\Error $e) {
            $data['namespace'] = null;
        }

        // Stub-specific metadata
        $data['phpDoc'] = $this->toJsonSafe($entity->getPhpDoc());
        $data['sinceVersion'] = $this->toJsonSafe($entity->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($entity->getRemovedVersion());

        // Serialize methods
        $data['methods'] = [];
        foreach ($entity->methods as $method) {
            $data['methods'][] = $this->serializeMethod($method);
        }

        // Serialize constants
        $data['constants'] = [];
        foreach ($entity->constants as $constant) {
            $data['constants'][] = $this->serializeClassConstant($constant);
        }

        return $data;
    }

    private function serializeEnum(PHPEnum $entity): array
    {
        $data = [
            '_type' => 'PHPEnum',
            'name' => $this->toJsonSafe($entity->getName()),
            'id' => $this->toJsonSafe($entity->getId()),
            'isFinal' => $this->toJsonSafe($entity->isFinal ?? null),
            'isReadonly' => $this->toJsonSafe($entity->isReadonly ?? null),
            'sourcePath' => $this->toJsonSafe($entity->getSourcePath()),
            'duplicates' => $this->toJsonSafe($entity->getDuplicates()),
        ];

        try {
            $data['namespace'] = $this->toJsonSafe($entity->getNamespace());
        } catch (\Error $e) {
            $data['namespace'] = null;
        }

        // Stub-specific metadata
        $data['phpDoc'] = $this->toJsonSafe($entity->getPhpDoc());
        $data['sinceVersion'] = $this->toJsonSafe($entity->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($entity->getRemovedVersion());

        // Serialize methods
        $data['methods'] = [];
        foreach ($entity->methods as $method) {
            $data['methods'][] = $this->serializeMethod($method);
        }

        // Serialize interfaces (just store the names)
        $data['interfaces'] = [];
        foreach ($entity->interfaces as $interface) {
            $data['interfaces'][] = $interface->getName();
        }

        return $data;
    }

    private function serializeConstant(PHPConstant $entity): array
    {
        $data = [
            '_type' => 'PHPConstant',
            'name' => $this->toJsonSafe($entity->getName()),
            'id' => $this->toJsonSafe($entity->getId()),
            'value' => $this->toJsonSafe($entity->value ?? null),
            'sourcePath' => $this->toJsonSafe($entity->getSourcePath()),
            'duplicates' => $this->toJsonSafe($entity->getDuplicates()),
        ];

        try {
            $data['namespace'] = $this->toJsonSafe($entity->getNamespace());
        } catch (\Error $e) {
            $data['namespace'] = null;
        }

        // Stub-specific metadata
        $data['phpDoc'] = $this->toJsonSafe($entity->getPhpDoc());
        $data['sinceVersion'] = $this->toJsonSafe($entity->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($entity->getRemovedVersion());

        return $data;
    }

    private function serializeMethod(PHPMethod $method): array
    {
        $data = [
            'name' => $method->getName(),
            'isStatic' => $method->isStatic(),
            'isFinal' => $method->isFinal(),
            'isAbstract' => $method->isAbstract(),
            'isDeprecated' => $method->isDeprecated(),
        ];

        // Serialize access modifier
        $access = $method->getAccess();
        if ($access !== null && method_exists($access, 'toString')) {
            $data['accessModifier'] = $access->toString();
        } else {
            $data['accessModifier'] = 'public';
        }

        // Serialize parameters
        $data['parameters'] = [];
        foreach ($method->getParameters() as $parameter) {
            $data['parameters'][] = $this->serializeParameter($parameter);
        }

        // Serialize return type (always a type object)
        $type = $method->getReturnTypeFromSignature();
        $data['returnType'] = $type?->toString() ?? null;

        $data['hasTentativeReturnType'] = $this->toJsonSafe($method->hasTentativeReturnType());

        // Stub-specific metadata
        $data['phpDoc'] = $this->toJsonSafe($method->getPhpDoc());
        $data['sinceVersion'] = $this->toJsonSafe($method->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($method->getRemovedVersion());
        $data['returnTypeFromPhpDoc'] = $this->toJsonSafe($method->getReturnTypeFromPhpDoc());
        $data['languageLevelTypes'] = $this->toJsonSafe($method->getLanguageLevelTypes());
        $data['defaultType'] = $this->toJsonSafe($method->getDefaultType());

        return $data;
    }

    private function serializeParameter(PHPParameter $parameter): array
    {
        $data = [
            'name' => $parameter->getName(),
            'position' => $parameter->getPosition(),
            'isOptional' => $parameter->isOptional(),
            'isVariadic' => $parameter->isVariadic(),
            'isPassedByReference' => $parameter->isPassedByReference(),
            'hasDefaultValue' => $parameter->hasDefaultValue(),
        ];

        // Serialize type (always a type object)
        $data['type'] = $parameter->getDeclaredType()->toString();

        // Serialize default value if available
        if ($parameter->hasDefaultValue()) {
            $data['defaultValue'] = $this->toJsonSafe($parameter->getDefaultValue());
        } else {
            $data['defaultValue'] = null;
        }

        // Stub-specific metadata
        $data['typeFromPhpDoc'] = $this->toJsonSafe($parameter->getTypeFromPhpDoc());
        $data['languageLevelTypes'] = $this->toJsonSafe($parameter->getLanguageLevelTypes());
        $data['defaultType'] = $this->toJsonSafe($parameter->getDefaultType());
        $data['sinceVersion'] = $this->toJsonSafe($parameter->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($parameter->getRemovedVersion());

        return $data;
    }

    private function serializeProperty(PHPProperty $property): array
    {
        $data = [
            'name' => $property->getName(),
            'isStatic' => $property->isStatic(),
            'isReadonly' => $property->isReadonly()
        ];

        // Serialize access modifier
        $access = $property->getAccess();
        if ($access !== null && method_exists($access, 'toString')) {
            $data['accessModifier'] = $access->toString();
        } else {
            $data['accessModifier'] = 'public';
        }

        // Serialize type (always a type object)
        $type = $property->getType();
        $data['type'] = $type?->toString() ?? null;

        // Stub-specific metadata
        $data['phpDoc'] = $this->toJsonSafe($property->getPhpDoc());
        $data['sinceVersion'] = $this->toJsonSafe($property->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($property->getRemovedVersion());
        $data['typeFromPhpDoc'] = $this->toJsonSafe($property->getTypeFromPhpDoc());
        $data['languageLevelTypes'] = $this->toJsonSafe($property->getLanguageLevelTypes());
        $data['defaultType'] = $this->toJsonSafe($property->getDefaultType());

        return $data;
    }

    private function serializeClassConstant(PHPClassConstant $constant): array
    {
        return [
            'name' => $constant->getName(),
            'value' => $this->toJsonSafe($constant->getValue()),
            'visibility' => $constant->visibility ?? 'public',
            'isFinal' => $constant->isFinal(),
        ];
    }

    // Deserialization methods...
    private function deserializeClass(array $data): PHPClass
    {
        $class = new PHPClass();
        $class->setName($data['name'] ?? null);
        $class->setNamespace($data['namespace'] ?? null);
        $class->setId($data['id'] ?? null);
        $class->isFinal = $data['isFinal'] ?? null;
        $class->isReadonly = $data['isReadonly'] ?? null;
        $class->setSourcePath($data['sourcePath'] ?? null);
        $class->setDuplicates($data['duplicates'] ?? []);

        // Stub-specific metadata
        $class->setPhpDoc($data['phpDoc'] ?? null);
        $class->setSinceVersion($data['sinceVersion'] ?? null);
        $class->setRemovedVersion($data['removedVersion'] ?? null);

        // Deserialize methods
        if (isset($data['methods']) && is_array($data['methods'])) {
            foreach ($data['methods'] as $methodData) {
                $class->methods[] = $this->deserializeMethod($methodData);
            }
        }

        // Deserialize properties
        if (isset($data['properties']) && is_array($data['properties'])) {
            foreach ($data['properties'] as $propertyData) {
                $class->properties[] = $this->deserializeProperty($propertyData);
            }
        }

        // Deserialize constants
        if (isset($data['constants']) && is_array($data['constants'])) {
            foreach ($data['constants'] as $constantData) {
                $class->constants[] = $this->deserializeClassConstant($constantData);
            }
        }

        return $class;
    }

    private function deserializeFunction(array $data): PHPFunction
    {
        $function = new PHPFunction();
        $function->setName($data['name'] ?? null);
        $function->setNamespace($data['namespace'] ?? null);
        $function->setId($data['id'] ?? null);
        $function->setDeprecated($data['isDeprecated'] ?? false);
        $function->setReturnTypeFromSignature($data['returnType'] ?? null);
        $function->setSourcePath($data['sourcePath'] ?? null);
        $function->setDuplicates($data['duplicates'] ?? []);
        $function->setHasTentativeReturnType($data['hasTentativeReturnType'] ?? false);

        // Stub-specific metadata
        $function->setPhpDoc($data['phpDoc'] ?? null);
        $function->setSinceVersion($data['sinceVersion'] ?? null);
        $function->setRemovedVersion($data['removedVersion'] ?? null);
        $function->setReturnTypeFromPhpDoc($data['returnTypeFromPhpDoc'] ?? null);
        $function->setLanguageLevelTypes($data['languageLevelTypes'] ?? null);
        $function->setDefaultType($data['defaultType'] ?? null);

        // Deserialize parameters
        if (isset($data['parameters']) && is_array($data['parameters'])) {
            $parameters = [];
            foreach ($data['parameters'] as $paramData) {
                $parameters[] = $this->deserializeParameter($paramData);
            }
            $function->setParameters($parameters);
        }

        return $function;
    }

    private function deserializeInterface(array $data): PHPInterface
    {
        $interface = new PHPInterface();
        $interface->setName($data['name'] ?? null);
        $interface->setNamespace($data['namespace'] ?? null);
        $interface->setId($data['id'] ?? null);
        $interface->setSourcePath($data['sourcePath'] ?? null);
        $interface->setDuplicates($data['duplicates'] ?? []);

        // Stub-specific metadata
        $interface->setPhpDoc($data['phpDoc'] ?? null);
        $interface->setSinceVersion($data['sinceVersion'] ?? null);
        $interface->setRemovedVersion($data['removedVersion'] ?? null);

        // Deserialize methods
        if (isset($data['methods']) && is_array($data['methods'])) {
            foreach ($data['methods'] as $methodData) {
                $interface->methods[] = $this->deserializeMethod($methodData);
            }
        }

        // Deserialize constants
        if (isset($data['constants']) && is_array($data['constants'])) {
            foreach ($data['constants'] as $constantData) {
                $interface->constants[] = $this->deserializeClassConstant($constantData);
            }
        }

        return $interface;
    }

    private function deserializeEnum(array $data): PHPEnum
    {
        $enum = new PHPEnum();
        $enum->setName($data['name'] ?? null);
        $enum->setNamespace($data['namespace'] ?? null);
        $enum->setId($data['id'] ?? null);
        $enum->isFinal = $data['isFinal'] ?? null;
        $enum->isReadonly = $data['isReadonly'] ?? null;
        $enum->setSourcePath($data['sourcePath'] ?? null);
        $enum->setDuplicates($data['duplicates'] ?? []);

        // Stub-specific metadata
        $enum->setPhpDoc($data['phpDoc'] ?? null);
        $enum->setSinceVersion($data['sinceVersion'] ?? null);
        $enum->setRemovedVersion($data['removedVersion'] ?? null);

        // Deserialize methods
        if (isset($data['methods']) && is_array($data['methods'])) {
            foreach ($data['methods'] as $methodData) {
                $enum->methods[] = $this->deserializeMethod($methodData);
            }
        }

        return $enum;
    }

    private function deserializeConstant(array $data): PHPConstant
    {
        $constant = new PHPConstant();
        $constant->setName($data['name'] ?? '');
        $constant->setNamespace($data['namespace'] ?? null);
        $constant->setId($data['id'] ?? null);
        $constant->value = $data['value'] ?? null;
        $constant->setSourcePath($data['sourcePath'] ?? null);
        $constant->setDuplicates($data['duplicates'] ?? []);

        // Stub-specific metadata
        $constant->setPhpDoc($data['phpDoc'] ?? null);
        $constant->setSinceVersion($data['sinceVersion'] ?? null);
        $constant->setRemovedVersion($data['removedVersion'] ?? null);

        return $constant;
    }

    private function deserializeMethod(array $data): PHPMethod
    {
        $method = new PHPMethod();
        $method->setName($data['name'] ?? '');
        $method->setIsStatic($data['isStatic'] ?? false);
        $method->setIsFinal($data['isFinal'] ?? false);
        $method->setIsAbstract($data['isAbstract'] ?? false);
        $method->setDeprecated($data['isDeprecated'] ?? false);

        // Deserialize access modifier
        $accessModifier = $data['accessModifier'] ?? 'public';
        if ($accessModifier === 'private') {
            $method->setAccess(new PrivateAccessModifier());
        } elseif ($accessModifier === 'protected') {
            $method->setAccess(new ProtectedAccessModifier());
        } else {
            $method->setAccess(new PublicAccessModifier());
        }

        // Deserialize parameters
        if (isset($data['parameters']) && is_array($data['parameters'])) {
            $parameters = [];
            foreach ($data['parameters'] as $paramData) {
                $parameters[] = $this->deserializeParameter($paramData);
            }
            $method->setParameters($parameters);
        }

        $method->setHasTentativeReturnType($data['hasTentativeReturnType'] ?? false);
        $method->setReturnTypeFromSignature($data['returnType'] ?? null);

        // Stub-specific metadata
        $method->setPhpDoc($data['phpDoc'] ?? null);
        $method->setSinceVersion($data['sinceVersion'] ?? null);
        $method->setRemovedVersion($data['removedVersion'] ?? null);
        $method->setReturnTypeFromPhpDoc($data['returnTypeFromPhpDoc'] ?? null);
        $method->setLanguageLevelTypes($data['languageLevelTypes'] ?? null);
        $method->setDefaultType($data['defaultType'] ?? null);

        return $method;
    }

    private function deserializeParameter(array $data): PHPParameter
    {
        $parameter = new PHPParameter($data['name'] ?? '');
        $parameter->setPosition($data['position'] ?? 0);
        $parameter->setIsOptional($data['isOptional'] ?? false);
        $parameter->setIsVariadic($data['isVariadic'] ?? false);
        $parameter->setIsPassedByReference($data['isPassedByReference'] ?? false);
        $parameter->setHasDefaultValue($data['hasDefaultValue'] ?? false);

        if (isset($data['type'])) {
            $parameter->setType($data['type']);
        }

        if (isset($data['defaultValue'])) {
            $parameter->setDefaultValue($data['defaultValue']);
        }

        // Stub-specific metadata
        $parameter->setTypeFromPhpDoc($data['typeFromPhpDoc'] ?? null);
        $parameter->setLanguageLevelTypes($data['languageLevelTypes'] ?? null);
        $parameter->setDefaultType($data['defaultType'] ?? null);
        $parameter->setSinceVersion($data['sinceVersion'] ?? null);
        $parameter->setRemovedVersion($data['removedVersion'] ?? null);

        return $parameter;
    }

    private function deserializeProperty(array $data): PHPProperty
    {
        $property = new PHPProperty();
        $property->setName($data['name'] ?? '');
        $property->setIsStatic($data['isStatic'] ?? false);
        $property->setIsReadonly($data['isReadonly'] ?? false);
        $property->setTypeFromSignature($data['type'] ?? null);

        // Deserialize access modifier
        $accessModifier = $data['accessModifier'] ?? 'public';
        if ($accessModifier === 'private') {
            $property->setAccess(new PrivateAccessModifier());
        } elseif ($accessModifier === 'protected') {
            $property->setAccess(new ProtectedAccessModifier());
        } else {
            $property->setAccess(new PublicAccessModifier());
        }

        if (isset($data['type'])) {
            $property->setTypeFromSignature($data['type']);
        }

        // Stub-specific metadata
        $property->setPhpDoc($data['phpDoc'] ?? null);
        $property->setSinceVersion($data['sinceVersion'] ?? null);
        $property->setRemovedVersion($data['removedVersion'] ?? null);
        $property->setTypeFromPhpDoc($data['typeFromPhpDoc'] ?? null);
        $property->setLanguageLevelTypes($data['languageLevelTypes'] ?? null);
        $property->setDefaultType($data['defaultType'] ?? null);

        return $property;
    }

    private function deserializeClassConstant(array $data): PHPClassConstant
    {
        $constant = new PHPClassConstant();
        $constant->setName($data['name'] ?? '');
        $constant->value = $data['value'] ?? null;
        $constant->visibility = $data['visibility'] ?? 'public';
        $constant->isFinal = $data['isFinal'] ?? false;
        return $constant;
    }
}
