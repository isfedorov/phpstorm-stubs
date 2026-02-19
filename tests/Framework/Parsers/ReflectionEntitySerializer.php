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
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;

/**
 * Serializer for reflection entities that only includes data available via PHP Reflection API.
 *
 * EXPLICITLY EXCLUDES stub-specific metadata:
 * - NO PhpDoc comments (not available via Reflection API)
 * - NO sinceVersion/removedVersion (stubs-only metadata)
 * - NO typeFromPhpDoc (PhpDoc not available)
 * - NO LanguageLevelTypeAware data (stubs-only attribute)
 *
 * INCLUDES reflection-specific data:
 * - Signature types (actual PHP types from runtime)
 * - Tentative return types (PHP 8.1+ feature)
 * - Method/property visibility and modifiers
 */
class ReflectionEntitySerializer implements EntitySerializerInterface
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
            // Check if object has toString() method (e.g., type objects)
            if (method_exists($value, 'toString')) {
                return $value->toString();
            }
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

        try {
            $data['namespace'] = $this->toJsonSafe($entity->getNamespace());
        } catch (\Error $e) {
            $data['namespace'] = null;
        }

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

    // Deserialization methods (same as before - no stub metadata)
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

        // Only set return type if provided and not null
        if (isset($data['returnType']) && $data['returnType'] !== null) {
            // For now, we only support string type names that can be converted to StandaloneType
            $function->setReturnTypeFromSignature(new StandaloneType($data['returnType']));
        }

        $function->setSourcePath($data['sourcePath'] ?? null);
        $function->setDuplicates($data['duplicates'] ?? []);
        $function->setHasTentativeReturnType($data['hasTentativeReturnType'] ?? false);

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

        // Only set return type if provided and not null
        if (isset($data['returnType']) && $data['returnType'] !== null) {
            $method->setReturnTypeFromSignature(new StandaloneType($data['returnType']));
        }

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

        // Only set type if provided and not null
        if (isset($data['type']) && $data['type'] !== null) {
            $parameter->setType(new StandaloneType($data['type']));
        }

        if (isset($data['defaultValue'])) {
            $parameter->setDefaultValue($data['defaultValue']);
        }

        return $parameter;
    }

    private function deserializeProperty(array $data): PHPProperty
    {
        $property = new PHPProperty();
        $property->setName($data['name'] ?? '');
        $property->setIsStatic($data['isStatic'] ?? false);
        $property->setIsReadonly($data['isReadonly'] ?? false);

        // Deserialize access modifier
        $accessModifier = $data['accessModifier'] ?? 'public';
        if ($accessModifier === 'private') {
            $property->setAccess(new PrivateAccessModifier());
        } elseif ($accessModifier === 'protected') {
            $property->setAccess(new ProtectedAccessModifier());
        } else {
            $property->setAccess(new PublicAccessModifier());
        }

        // Only set type if provided and not null
        if (isset($data['type']) && $data['type'] !== null) {
            $property->setTypeFromSignature(new StandaloneType($data['type']));
        }

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
