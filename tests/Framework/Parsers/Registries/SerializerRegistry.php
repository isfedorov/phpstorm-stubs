<?php

namespace StubTests\Sources\Parsers\Registries;

use StubTests\Sources\Parsers\Serializers\EntityTypeSerializerInterface;

/**
 * Registry for managing entity-specific serializers.
 * Implements the strategy pattern to find the appropriate serializer for each entity type.
 */
class SerializerRegistry
{
    /** @var EntityTypeSerializerInterface[] */
    private array $serializers = [];

    /**
     * Register a new entity serializer.
     *
     * @param EntityTypeSerializerInterface $serializer
     */
    public function register(EntityTypeSerializerInterface $serializer): void
    {
        $this->serializers[] = $serializer;
    }

    /**
     * Find a serializer that supports the given entity.
     *
     * @param mixed $entity The entity to find a serializer for
     * @return EntityTypeSerializerInterface|null The matching serializer, or null if none found
     */
    public function findSerializer($entity): ?EntityTypeSerializerInterface
    {
        foreach ($this->serializers as $serializer) {
            if ($serializer->supports($entity)) {
                return $serializer;
            }
        }

        return null;
    }

    /**
     * Find a serializer that can handle the given entity type name.
     *
     * @param string $typeName The entity type name (e.g., 'PHPClass')
     * @return EntityTypeSerializerInterface|null The matching serializer, or null if none found
     */
    public function findSerializerByType(string $typeName): ?EntityTypeSerializerInterface
    {
        foreach ($this->serializers as $serializer) {
            // Create a temporary mock object to test support
            $mockEntity = new class($typeName) {
                public function __construct(private string $type) {}
                public function getType(): string { return $this->type; }
            };

            // Check if serializer supports by checking the entity class name pattern
            $reflection = new \ReflectionClass($serializer);
            $serializerName = $reflection->getShortName();

            // Extract entity type from serializer name (e.g., PHPClassSerializer -> PHPClass)
            if (str_starts_with($serializerName, $typeName)) {
                return $serializer;
            }
        }

        return null;
    }

    /**
     * Get all registered serializers.
     *
     * @return EntityTypeSerializerInterface[]
     */
    public function getAllSerializers(): array
    {
        return $this->serializers;
    }
}
