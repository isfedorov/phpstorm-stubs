<?php

namespace StubTests\Sources\Parsers\Serializers;

use StubTests\Sources\Parsers\Entities\Model\PHPConstant;
use StubTests\Sources\Parsers\PhpDocStorage;

/**
 * Serializer for PHPConstant entities.
 */
class PHPConstantSerializer implements EntityTypeSerializerInterface
{
    use SerializerHelperTrait;

    public function supports($entity): bool
    {
        return $entity instanceof PHPConstant;
    }

    public function serialize($entity, ?PhpDocStorage $phpDocStorage = null): array
    {
        if (!$entity instanceof PHPConstant) {
            throw new \InvalidArgumentException('Expected PHPConstant entity');
        }

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
        $data['phpDoc'] = $this->serializePhpDoc($entity->getId(), $entity->getPhpDoc(), $phpDocStorage);
        $data['sinceVersion'] = $this->toJsonSafe($entity->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($entity->getRemovedVersion());

        return $data;
    }

    public function deserialize(array $data, ?PhpDocStorage $phpDocStorage = null)
    {
        $constant = new PHPConstant();
        $constant->setName($data['name'] ?? null);
        $constant->setNamespace($data['namespace'] ?? null);
        $constant->setId($data['id'] ?? null);
        $constant->value = $data['value'] ?? null;
        $constant->setSourcePath($data['sourcePath'] ?? null);
        $constant->setDuplicates($data['duplicates'] ?? []);

        // Stub-specific metadata
        $constantId = $data['id'] ?? null;
        $constant->setPhpDoc($this->deserializePhpDoc($constantId, $data['phpDoc'] ?? null, $phpDocStorage));
        $constant->setSinceVersion($data['sinceVersion'] ?? null);
        $constant->setRemovedVersion($data['removedVersion'] ?? null);

        return $constant;
    }
}
