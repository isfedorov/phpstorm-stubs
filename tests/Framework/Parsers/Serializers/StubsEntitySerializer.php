<?php

namespace StubTests\Sources\Parsers\Serializers;

use StubTests\Sources\Parsers\PhpDocStorage;
use StubTests\Sources\Parsers\Registries\SerializerRegistry;

/**
 * Serializer for stub entities that includes all stub-specific metadata:
 * - PhpDoc comments (raw text)
 * - Version information (sinceVersion, removedVersion)
 * - Type information from multiple sources (signature, PhpDoc, LanguageLevelTypeAware)
 * - LanguageLevelTypeAware attribute data (version-specific types)
 *
 * This class now acts as a facade/coordinator that delegates to entity-specific serializers
 * using the registry pattern.
 */
class StubsEntitySerializer implements EntitySerializerInterface
{
    private ?PhpDocStorage $phpDocStorage = null;
    private SerializerRegistry $registry;

    public function __construct(?PhpDocStorage $phpDocStorage = null)
    {
        $this->phpDocStorage = $phpDocStorage;
        $this->registry = new SerializerRegistry();

        // Register all entity-specific serializers
        $this->registry->register(new PHPClassSerializer());
        $this->registry->register(new PHPFunctionSerializer());
        $this->registry->register(new PHPInterfaceSerializer());
        $this->registry->register(new PHPEnumSerializer());
        $this->registry->register(new PHPConstantSerializer());
    }

    public function serialize($entity): array
    {
        // Find appropriate serializer for this entity
        $serializer = $this->registry->findSerializer($entity);

        if ($serializer !== null) {
            return $serializer->serialize($entity, $this->phpDocStorage);
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

        // For known types, find the appropriate serializer
        if ($type !== 'Unknown') {
            foreach ($this->registry->getAllSerializers() as $serializer) {
                // Try to match by checking if serializer class name contains the type
                $serializerClass = get_class($serializer);
                if (str_contains($serializerClass, $type)) {
                    return $serializer->deserialize($data, $this->phpDocStorage);
                }
            }
        }

        // Fallback for unknown types
        return isset($data['data']) ? unserialize($data['data']) : null;
    }
}
