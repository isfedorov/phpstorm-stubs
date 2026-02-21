<?php

namespace StubTests\Sources\Parsers\Serializers;

use StubTests\Sources\Parsers\Entities\Model\PHPInterface;
use StubTests\Sources\Parsers\PhpDocStorage;

/**
 * Serializer for PHPInterface entities.
 */
class PHPInterfaceSerializer implements EntityTypeSerializerInterface
{
    use SerializerHelperTrait;

    public function supports($entity): bool
    {
        return $entity instanceof PHPInterface;
    }

    public function serialize($entity, ?PhpDocStorage $phpDocStorage = null): array
    {
        if (!$entity instanceof PHPInterface) {
            throw new \InvalidArgumentException('Expected PHPInterface entity');
        }

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
        $data['phpDoc'] = $this->serializePhpDoc($entity->getId(), $entity->getPhpDoc(), $phpDocStorage);
        $data['sinceVersion'] = $this->toJsonSafe($entity->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($entity->getRemovedVersion());

        // Serialize methods
        $data['methods'] = [];
        foreach ($entity->methods as $method) {
            $data['methods'][] = $this->serializeMethod($method, $entity->getId(), $phpDocStorage);
        }

        // Serialize constants
        $data['constants'] = [];
        foreach ($entity->constants as $constant) {
            $data['constants'][] = $this->serializeClassConstant($constant);
        }

        return $data;
    }

    public function deserialize(array $data, ?PhpDocStorage $phpDocStorage = null)
    {
        $interface = new PHPInterface();
        $interface->setName($data['name'] ?? null);
        $interface->setNamespace($data['namespace'] ?? null);
        $interface->setId($data['id'] ?? null);
        $interface->setSourcePath($data['sourcePath'] ?? null);
        $interface->setDuplicates($data['duplicates'] ?? []);

        // Stub-specific metadata
        $interfaceId = $data['id'] ?? null;
        $interface->setPhpDoc($this->deserializePhpDoc($interfaceId, $data['phpDoc'] ?? null, $phpDocStorage));
        $interface->setSinceVersion($data['sinceVersion'] ?? null);
        $interface->setRemovedVersion($data['removedVersion'] ?? null);

        // Deserialize methods
        if (isset($data['methods']) && is_array($data['methods'])) {
            foreach ($data['methods'] as $methodData) {
                $interface->methods[] = $this->deserializeMethod($methodData, $interfaceId, $phpDocStorage);
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
}
