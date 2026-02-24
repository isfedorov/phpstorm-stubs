<?php

namespace StubTests\Sources\Parsers\Serializers;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\PhpDocStorage;

/**
 * Serializer for PHPClass entities.
 */
class PHPClassSerializer implements EntityTypeSerializerInterface
{
    use SerializerHelperTrait;

    public function supports($entity): bool
    {
        return $entity instanceof PHPClass;
    }

    public function serialize($entity, ?PhpDocStorage $phpDocStorage = null): array
    {
        if (!$entity instanceof PHPClass) {
            throw new \InvalidArgumentException('Expected PHPClass entity');
        }

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
        $data['phpDoc'] = $this->serializePhpDoc($entity->getId(), $entity->getPhpDoc(), $phpDocStorage);
        $data['sinceVersion'] = $this->toJsonSafe($entity->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($entity->getRemovedVersion());

        // Serialize methods
        $data['methods'] = [];
        foreach ($entity->methods as $method) {
            $data['methods'][] = $this->serializeMethod($method, $entity->getId(), $phpDocStorage);
        }

        // Serialize properties
        $data['properties'] = [];
        foreach ($entity->properties as $property) {
            $data['properties'][] = $this->serializeProperty($property, $entity->getId(), $phpDocStorage);
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

    public function deserialize(array $data, ?PhpDocStorage $phpDocStorage = null)
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
        $classId = $data['id'] ?? null;
        $class->setPhpDoc($this->deserializePhpDoc($classId, $data['phpDoc'] ?? null, $phpDocStorage));
        $class->setSinceVersion($data['sinceVersion'] ?? null);
        $class->setRemovedVersion($data['removedVersion'] ?? null);

        // Deserialize methods
        if (isset($data['methods']) && is_array($data['methods'])) {
            foreach ($data['methods'] as $methodData) {
                $class->methods[] = $this->deserializeMethod($methodData, $classId, $phpDocStorage);
            }
        }

        // Deserialize properties
        if (isset($data['properties']) && is_array($data['properties'])) {
            foreach ($data['properties'] as $propertyData) {
                $class->properties[] = $this->deserializeProperty($propertyData, $classId, $phpDocStorage);
            }
        }

        // Deserialize constants
        if (isset($data['constants']) && is_array($data['constants'])) {
            foreach ($data['constants'] as $constantData) {
                $class->constants[] = $this->deserializeClassConstant($constantData);
            }
        }

        // Restore parent class from stored name
        if (!empty($data['parentClass'])) {
            $parentClass = new PHPClass();
            $parentClass->setName($data['parentClass']);
            $class->parentClass = $parentClass;
        }

        // Restore interfaces from stored names
        if (isset($data['interfaces']) && is_array($data['interfaces'])) {
            foreach ($data['interfaces'] as $interfaceName) {
                if (!empty($interfaceName)) {
                    $interface = new PHPInterface();
                    $interface->setName($interfaceName);
                    $class->interfaces[] = $interface;
                }
            }
        }

        return $class;
    }
}
