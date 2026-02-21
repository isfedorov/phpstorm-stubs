<?php

namespace StubTests\Sources\Parsers\Serializers;

use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\PhpDocStorage;

/**
 * Serializer for PHPFunction entities.
 */
class PHPFunctionSerializer implements EntityTypeSerializerInterface
{
    use SerializerHelperTrait;

    public function supports($entity): bool
    {
        return $entity instanceof PHPFunction;
    }

    public function serialize($entity, ?PhpDocStorage $phpDocStorage = null): array
    {
        if (!$entity instanceof PHPFunction) {
            throw new \InvalidArgumentException('Expected PHPFunction entity');
        }

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
        $data['phpDoc'] = $this->serializePhpDoc($entity->getId(), $entity->getPhpDoc(), $phpDocStorage);
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
                $data['parameters'][] = $this->serializeParameter($param, $phpDocStorage);
            }
        } catch (\Error $e) {
            $data['parameters'] = [];
        }

        return $data;
    }

    public function deserialize(array $data, ?PhpDocStorage $phpDocStorage = null)
    {
        $function = new PHPFunction();
        $function->setName($data['name'] ?? null);
        $function->setNamespace($data['namespace'] ?? null);
        $function->setId($data['id'] ?? null);
        $function->setSourcePath($data['sourcePath'] ?? null);
        $function->setDuplicates($data['duplicates'] ?? []);
        $function->setDeprecated($data['isDeprecated'] ?? false);

        $function->setHasTentativeReturnType($data['hasTentativeReturnType'] ?? false);

        // Only set return type if provided and not null
        if (isset($data['returnType']) && $data['returnType'] !== null) {
            $function->setReturnTypeFromSignature(new \StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType($data['returnType']));
        }

        // Stub-specific metadata
        $functionId = $data['id'] ?? null;
        $function->setPhpDoc($this->deserializePhpDoc($functionId, $data['phpDoc'] ?? null, $phpDocStorage));
        $function->setSinceVersion($data['sinceVersion'] ?? null);
        $function->setRemovedVersion($data['removedVersion'] ?? null);
        $function->setReturnTypeFromPhpDoc($data['returnTypeFromPhpDoc'] ?? null);
        $function->setLanguageLevelTypes($data['languageLevelTypes'] ?? null);
        $function->setDefaultType($data['defaultType'] ?? null);

        // Deserialize parameters
        if (isset($data['parameters']) && is_array($data['parameters'])) {
            $parameters = [];
            foreach ($data['parameters'] as $paramData) {
                $parameters[] = $this->deserializeParameter($paramData, $phpDocStorage);
            }
            $function->setParameters($parameters);
        }

        return $function;
    }
}
