<?php

namespace StubTests\Sources\Parsers\Serializers;

use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\PhpDocStorage;

/**
 * Serializer for PHPEnum entities.
 */
class PHPEnumSerializer implements EntityTypeSerializerInterface
{
    use SerializerHelperTrait;

    public function supports($entity): bool
    {
        return $entity instanceof PHPEnum;
    }

    public function serialize($entity, ?PhpDocStorage $phpDocStorage = null): array
    {
        if (!$entity instanceof PHPEnum) {
            throw new \InvalidArgumentException('Expected PHPEnum entity');
        }

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
        $data['phpDoc'] = $this->serializePhpDoc($entity->getId(), $entity->getPhpDoc(), $phpDocStorage);
        $data['sinceVersion'] = $this->toJsonSafe($entity->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($entity->getRemovedVersion());

        // Serialize cases
        $data['cases'] = $entity->cases;

        // Serialize constants
        $data['constants'] = [];
        foreach ($entity->constants as $constant) {
            $data['constants'][] = $this->serializeClassConstant($constant);
        }

        // Serialize methods
        $data['methods'] = [];
        foreach ($entity->methods as $method) {
            $data['methods'][] = $this->serializeMethod($method, $entity->getId(), $phpDocStorage);
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
        $enum = new PHPEnum();
        $enum->setName($data['name'] ?? null);
        $enum->setNamespace($data['namespace'] ?? null);
        $enum->setId($data['id'] ?? null);
        $enum->isFinal = $data['isFinal'] ?? null;
        $enum->isReadonly = $data['isReadonly'] ?? null;
        $enum->setSourcePath($data['sourcePath'] ?? null);
        $enum->setDuplicates($data['duplicates'] ?? []);

        // Stub-specific metadata
        $enumId = $data['id'] ?? null;
        $enum->setPhpDoc($this->deserializePhpDoc($enumId, $data['phpDoc'] ?? null, $phpDocStorage));
        $enum->setSinceVersion($data['sinceVersion'] ?? null);
        $enum->setRemovedVersion($data['removedVersion'] ?? null);

        // Deserialize cases
        $enum->cases = isset($data['cases']) && is_array($data['cases']) ? $data['cases'] : [];

        // Deserialize constants
        if (isset($data['constants']) && is_array($data['constants'])) {
            foreach ($data['constants'] as $constantData) {
                $enum->constants[] = $this->deserializeClassConstant($constantData);
            }
        }

        // Deserialize methods
        if (isset($data['methods']) && is_array($data['methods'])) {
            foreach ($data['methods'] as $methodData) {
                $enum->methods[] = $this->deserializeMethod($methodData, $enumId, $phpDocStorage);
            }
        }

        // Restore interfaces from stored names
        if (isset($data['interfaces']) && is_array($data['interfaces'])) {
            foreach ($data['interfaces'] as $interfaceName) {
                if (!empty($interfaceName)) {
                    $interface = new \StubTests\Sources\Parsers\Entities\Model\PHPInterface();
                    $interface->setName($interfaceName);
                    $enum->interfaces[] = $interface;
                }
            }
        }

        return $enum;
    }
}
