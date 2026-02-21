<?php

namespace StubTests\Sources\Parsers\Serializers;

use StubTests\Framework\Parsers\Entities\Model\Access\PrivateAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\ProtectedAccessModifier;
use StubTests\Framework\Parsers\Entities\Model\Access\PublicAccessModifier;
use StubTests\Sources\Parsers\Entities\Model\PHPClassConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPMethod;
use StubTests\Sources\Parsers\Entities\Model\PHPParameter;
use StubTests\Sources\Parsers\Entities\Model\PHPProperty;
use StubTests\Sources\Parsers\Entities\Model\Types\StandaloneType;
use StubTests\Sources\Parsers\PhpDocStorage;

/**
 * Shared helper methods for serializers.
 * Contains common functionality used across different entity serializers.
 */
trait SerializerHelperTrait
{
    /**
     * Convert value to JSON-safe format, filtering out resources and closures
     */
    protected function toJsonSafe($value)
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
            // Skip complex objects that aren't basic types
            return '[object:' . get_class($value) . ']';
        }

        if (is_array($value)) {
            return array_map([$this, 'toJsonSafe'], $value);
        }

        return $value;
    }

    /**
     * Handle PhpDoc serialization - either store in separate file or inline
     */
    protected function serializePhpDoc(?string $entityId, ?string $phpDoc, ?PhpDocStorage $phpDocStorage): ?string
    {
        if ($phpDocStorage !== null && $entityId !== null) {
            // Store in separate PhpDoc storage
            $phpDocStorage->setPhpDoc($entityId, $phpDoc);
            return null; // Return null to indicate it's stored externally
        }
        // Store inline
        return $this->toJsonSafe($phpDoc);
    }

    /**
     * Deserialize PhpDoc - either from inline data or external storage
     */
    protected function deserializePhpDoc(?string $entityId, ?string $inlinePhpDoc, ?PhpDocStorage $phpDocStorage): ?string
    {
        // If inline PhpDoc is present, use it
        if ($inlinePhpDoc !== null) {
            return $inlinePhpDoc;
        }

        // Otherwise, try to load from external storage
        if ($phpDocStorage !== null && $entityId !== null) {
            return $phpDocStorage->getPhpDoc($entityId);
        }

        return null;
    }

    /**
     * Serialize a method to array format
     */
    protected function serializeMethod(PHPMethod $method, ?string $parentId, ?PhpDocStorage $phpDocStorage): array
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
            $data['parameters'][] = $this->serializeParameter($parameter, $phpDocStorage);
        }

        // Serialize return type (always a type object)
        $type = $method->getReturnTypeFromSignature();
        $data['returnType'] = $type?->toString() ?? null;

        $data['hasTentativeReturnType'] = $this->toJsonSafe($method->hasTentativeReturnType());

        // Stub-specific metadata - use parent ID + method name for PhpDoc storage
        $methodId = $parentId ? $parentId . '::' . $method->getName() : null;
        $data['phpDoc'] = $this->serializePhpDoc($methodId, $method->getPhpDoc(), $phpDocStorage);
        $data['sinceVersion'] = $this->toJsonSafe($method->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($method->getRemovedVersion());
        $data['returnTypeFromPhpDoc'] = $this->toJsonSafe($method->getReturnTypeFromPhpDoc());
        $data['languageLevelTypes'] = $this->toJsonSafe($method->getLanguageLevelTypes());
        $data['defaultType'] = $this->toJsonSafe($method->getDefaultType());

        return $data;
    }

    /**
     * Serialize a parameter to array format
     */
    protected function serializeParameter(PHPParameter $parameter, ?PhpDocStorage $phpDocStorage): array
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

    /**
     * Serialize a property to array format
     */
    protected function serializeProperty(PHPProperty $property, ?string $parentId, ?PhpDocStorage $phpDocStorage): array
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

        // Stub-specific metadata - use parent ID + property name for PhpDoc storage
        $propertyId = $parentId ? $parentId . '::$' . $property->getName() : null;
        $data['phpDoc'] = $this->serializePhpDoc($propertyId, $property->getPhpDoc(), $phpDocStorage);
        $data['sinceVersion'] = $this->toJsonSafe($property->getSinceVersion());
        $data['removedVersion'] = $this->toJsonSafe($property->getRemovedVersion());
        $data['typeFromPhpDoc'] = $this->toJsonSafe($property->getTypeFromPhpDoc());
        $data['languageLevelTypes'] = $this->toJsonSafe($property->getLanguageLevelTypes());
        $data['defaultType'] = $this->toJsonSafe($property->getDefaultType());

        return $data;
    }

    /**
     * Serialize a class constant to array format
     */
    protected function serializeClassConstant(PHPClassConstant $constant): array
    {
        return [
            'name' => $constant->getName(),
            'value' => $this->toJsonSafe($constant->getValue()),
            'visibility' => $constant->visibility ?? 'public',
            'isFinal' => $constant->isFinal(),
        ];
    }

    /**
     * Deserialize a method from array format
     */
    protected function deserializeMethod(array $data, ?string $parentId, ?PhpDocStorage $phpDocStorage): PHPMethod
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
                $parameters[] = $this->deserializeParameter($paramData, $phpDocStorage);
            }
            $method->setParameters($parameters);
        }

        $method->setHasTentativeReturnType($data['hasTentativeReturnType'] ?? false);

        // Only set return type if provided and not null
        if (isset($data['returnType']) && $data['returnType'] !== null) {
            $method->setReturnTypeFromSignature(new StandaloneType($data['returnType']));
        }

        // Stub-specific metadata
        $methodName = $data['name'] ?? '';
        $methodId = $parentId ? $parentId . '::' . $methodName : null;
        $method->setPhpDoc($this->deserializePhpDoc($methodId, $data['phpDoc'] ?? null, $phpDocStorage));
        $method->setSinceVersion($data['sinceVersion'] ?? null);
        $method->setRemovedVersion($data['removedVersion'] ?? null);
        $method->setReturnTypeFromPhpDoc($data['returnTypeFromPhpDoc'] ?? null);
        $method->setLanguageLevelTypes($data['languageLevelTypes'] ?? null);
        $method->setDefaultType($data['defaultType'] ?? null);

        return $method;
    }

    /**
     * Deserialize a parameter from array format
     */
    protected function deserializeParameter(array $data, ?PhpDocStorage $phpDocStorage): PHPParameter
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

        // Stub-specific metadata
        $parameter->setTypeFromPhpDoc($data['typeFromPhpDoc'] ?? null);
        $parameter->setLanguageLevelTypes($data['languageLevelTypes'] ?? null);
        $parameter->setDefaultType($data['defaultType'] ?? null);
        $parameter->setSinceVersion($data['sinceVersion'] ?? null);
        $parameter->setRemovedVersion($data['removedVersion'] ?? null);

        return $parameter;
    }

    /**
     * Deserialize a property from array format
     */
    protected function deserializeProperty(array $data, ?string $parentId, ?PhpDocStorage $phpDocStorage): PHPProperty
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

        // Stub-specific metadata
        $propertyName = $data['name'] ?? '';
        $propertyId = $parentId ? $parentId . '::$' . $propertyName : null;
        $property->setPhpDoc($this->deserializePhpDoc($propertyId, $data['phpDoc'] ?? null, $phpDocStorage));
        $property->setSinceVersion($data['sinceVersion'] ?? null);
        $property->setRemovedVersion($data['removedVersion'] ?? null);
        $property->setTypeFromPhpDoc($data['typeFromPhpDoc'] ?? null);
        $property->setLanguageLevelTypes($data['languageLevelTypes'] ?? null);
        $property->setDefaultType($data['defaultType'] ?? null);

        return $property;
    }

    /**
     * Deserialize a class constant from array format
     */
    protected function deserializeClassConstant(array $data): PHPClassConstant
    {
        $constant = new PHPClassConstant();
        $constant->setName($data['name'] ?? null);
        $constant->setValue($data['value'] ?? null);
        $constant->visibility = $data['visibility'] ?? 'public';
        $constant->setFinal($data['isFinal'] ?? false);

        return $constant;
    }
}
