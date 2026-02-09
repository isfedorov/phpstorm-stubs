<?php

namespace StubTests\Sources\Parsers;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\Entities\Model\PrivateAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\ProtectedAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\PublicAccessModifier;

class JsonParsedDataStorage implements ParsedDataPersistentStorageProvider
{
    private string $pathToJsonFile;
    private array $entities = [];
    private bool $loaded = false;

    public function __construct(string $pathToJsonFile, bool $loadExisting = true)
    {
        $this->pathToJsonFile = $pathToJsonFile;
        if ($loadExisting) {
            $this->load();
        } else {
            $this->loaded = true;
        }
    }

    public function getEntities()
    {
        return $this->entities;
    }

    public function addEntity($entity)
    {
        $this->entities[] = $entity;
    }

    public function save(): void
    {
        $serializedData = [];
        foreach ($this->entities as $entity) {
            try {
                $serializedData[] = $this->serializeEntity($entity);
            } catch (\Exception $e) {
                // Skip entities that can't be serialized
                error_log("Warning: Could not serialize entity: " . $e->getMessage());
                continue;
            }
        }

        $dir = dirname($this->pathToJsonFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        // Use JSON_PARTIAL_OUTPUT_ON_ERROR to handle encoding errors gracefully
        $json = json_encode(
            $serializedData,
            JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES
        );

        if ($json === false || $json === 'null') {
            throw new \RuntimeException('JSON encoding failed: ' . json_last_error_msg());
        }

        $bytes = file_put_contents($this->pathToJsonFile, $json);

        if ($bytes === false) {
            throw new \RuntimeException('Failed to write to file: ' . $this->pathToJsonFile);
        }
    }

    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        if (!file_exists($this->pathToJsonFile)) {
            $this->entities = [];
            $this->loaded = true;
            return;
        }

        $jsonContent = file_get_contents($this->pathToJsonFile);
        if ($jsonContent === false || trim($jsonContent) === '') {
            $this->entities = [];
            $this->loaded = true;
            return;
        }

        $data = json_decode($jsonContent, true);
        if (!is_array($data)) {
            $this->entities = [];
            $this->loaded = true;
            return;
        }

        foreach ($data as $entityData) {
            if (isset($entityData['_type'])) {
                $this->entities[] = $this->deserializeEntity($entityData);
            }
        }

        $this->loaded = true;
    }

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

    private function serializeEntity($entity): array
    {
        if ($entity instanceof PHPClass) {
            $data = [
                '_type' => 'PHPClass',
                'name' => $this->toJsonSafe($entity->getName()),
                'id' => $this->toJsonSafe($entity->getId()),
                'isFinal' => $this->toJsonSafe($entity->isFinal ?? null),
                'isReadonly' => $this->toJsonSafe($entity->isReadonly ?? null),
                'sourcePath' => $this->toJsonSafe($entity->getSourcePath()),
                'duplicates' => $this->toJsonSafe($entity->getDuplicates()),
            ];

            // Safely get namespace to avoid uninitialized property error
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

        if ($entity instanceof PHPFunction) {
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
                $data['returnType'] = $this->toJsonSafe($entity->getReturnTypeFromSignature());
                $data['hasTentativeReturnType'] = $this->toJsonSafe($entity->hasTentativeReturnType());
            } catch (\Error $e) {
                $data['returnType'] = null;
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

        if ($entity instanceof PHPInterface) {
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

        if ($entity instanceof PHPEnum) {
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

        if ($entity instanceof PHPConstant) {
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

        // Fallback for unknown entity types
        return [
            '_type' => 'Unknown',
            'className' => get_class($entity),
            'data' => serialize($entity)
        ];
    }

    private function deserializeEntity(array $data)
    {
        if ($data['_type'] === 'PHPClass') {
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

            // Note: parentClass and interfaces are stored as names only
            // They would need to be resolved from the entity collection if needed

            return $class;
        }

        if ($data['_type'] === 'PHPFunction') {
            $function = new PHPFunction();
            $function->setName($data['name'] ?? null);
            $function->setNamespace($data['namespace'] ?? null);
            $function->setId($data['id'] ?? null);
            $function->setDeprecated($data['isDeprecated'] ?? false);
            $function->setReturnTypeFromSignature($data['returnType'] ?? null);
            $function->setSourcePath($data['sourcePath'] ?? null);
            $function->setDuplicates($data['duplicates'] ?? []);

            // Deserialize parameters
            if (isset($data['parameters']) && is_array($data['parameters'])) {
                $parameters = [];
                foreach ($data['parameters'] as $paramData) {
                    $parameters[] = $this->deserializeParameter($paramData);
                }
                $function->setParameters($parameters);
            }
            $function->setHasTentativeReturnType($data['hasTentativeReturnType'] ?? false);
            return $function;
        }

        if ($data['_type'] === 'PHPInterface') {
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

        if ($data['_type'] === 'PHPEnum') {
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

            // Note: interfaces are stored as names only

            return $enum;
        }

        if ($data['_type'] === 'PHPConstant') {
            $constant = new PHPConstant();
            $constant->setName($data['name'] ?? null);
            $constant->setNamespace($data['namespace'] ?? null);
            $constant->setId($data['id'] ?? null);
            $constant->value = $data['value'] ?? null;
            $constant->setSourcePath($data['sourcePath'] ?? null);
            $constant->setDuplicates($data['duplicates'] ?? []);
            return $constant;
        }

        // Fallback for unknown types
        if (isset($data['data'])) {
            return unserialize($data['data']);
        }

        return null;
    }


    /**
     * Serialize a PHPMethod to array
     */
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

        $type = $method->getReturnTypeFromSignature();
        if (is_array($type)) {
            // Intersection type stored as array - format as Type1&Type2&Type3
            $data['returnType'] = implode('&', $type);
        } elseif (is_object($type) && method_exists($type, 'toString')) {
            // Call toString() on type objects to get human-readable representation
            $data['returnType'] = $type->toString();
        } elseif (is_object($type)) {
            // Fallback for objects without toString()
            $data['returnType'] = null;
        } else {
            // Primitive value (shouldn't normally happen, but handle it)
            $data['returnType'] = $type;
        }
        $data['hasTentativeReturnType'] = $this->toJsonSafe($method->hasTentativeReturnType());
        return $data;
    }

    /**
     * Deserialize a PHPMethod from array
     */
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

        return $method;
    }

    /**
     * Serialize a PHPParameter to array
     */
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

        // Serialize type
        $type = $parameter->getDeclaredType();
        if (is_array($type)) {
            // Intersection type stored as array - format as Type1&Type2&Type3
            $data['type'] = implode('&', $type);
        } elseif (is_object($type) && method_exists($type, 'toString')) {
            // Call toString() on type objects to get human-readable representation
            $data['type'] = $type->toString();
        } elseif (is_object($type)) {
            // Fallback for objects without toString()
            $data['type'] = null;
        } else {
            // Primitive value (shouldn't normally happen, but handle it)
            $data['type'] = $type;
        }

        // Serialize default value if available
        if ($parameter->hasDefaultValue()) {
            $data['defaultValue'] = $this->toJsonSafe($parameter->getDefaultValue());
        } else {
            $data['defaultValue'] = null;
        }

        return $data;
    }

    /**
     * Deserialize a PHPParameter from array
     */
    private function deserializeParameter(array $data): PHPParameter
    {
        $parameter = new PHPParameter($data['name'] ?? '');
        $parameter->setPosition($data['position'] ?? 0);
        $parameter->setIsOptional($data['isOptional'] ?? false);
        $parameter->setIsVariadic($data['isVariadic'] ?? false);
        $parameter->setIsPassedByReference($data['isPassedByReference'] ?? false);
        $parameter->setHasDefaultValue($data['hasDefaultValue'] ?? false);

        // Note: Type deserialization is simplified - we store the serialized representation
        // Full type reconstruction would require parsing the type strings
        if (isset($data['type'])) {
            $parameter->setType($data['type']);
        }

        if (isset($data['defaultValue'])) {
            $parameter->setDefaultValue($data['defaultValue']);
        }

        return $parameter;
    }

    /**
     * Serialize a PHPProperty to array
     */
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

        $type = $property->getType();
        if (is_array($type)) {
            // Intersection type stored as array - format as Type1&Type2&Type3
            $data['type'] = implode('&', $type);
        } elseif (is_object($type) && method_exists($type, 'toString')) {
            // Call toString() on type objects to get human-readable representation
            $data['type'] = $type->toString();
        } elseif (is_object($type)) {
            // Fallback for objects without toString()
            $data['type'] = null;
        } else {
            // Primitive value (shouldn't normally happen, but handle it)
            $data['type'] = $type;
        }

        return $data;
    }

    /**
     * Deserialize a PHPProperty from array
     */
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

        return $property;
    }

    /**
     * Serialize a PHPClassConstant to array
     */
    private function serializeClassConstant(PHPClassConstant $constant): array
    {
        return [
            'name' => $constant->getName(),
            'value' => $this->toJsonSafe($constant->getValue()),
            'visibility' => $constant->visibility ?? 'public',
            'isFinal' => $constant->isFinal(),
        ];
    }

    /**
     * Deserialize a PHPClassConstant from array
     */
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
