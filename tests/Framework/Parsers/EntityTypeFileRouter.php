<?php

namespace StubTests\Sources\Parsers;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;

/**
 * Routes entities to appropriate storage files based on their type.
 */
class EntityTypeFileRouter
{
    public const FILE_CLASSES = 'Classes';
    public const FILE_FUNCTIONS = 'Functions';
    public const FILE_INTERFACES = 'Interfaces';
    public const FILE_ENUMS = 'Enums';
    public const FILE_CONSTANTS = 'Constants';

    /**
     * Get file identifier for an entity
     */
    public function getFileForEntity($entity): string
    {
        if ($entity instanceof PHPClass) {
            return self::FILE_CLASSES;
        }

        if ($entity instanceof PHPFunction) {
            return self::FILE_FUNCTIONS;
        }

        if ($entity instanceof PHPInterface) {
            return self::FILE_INTERFACES;
        }

        if ($entity instanceof PHPEnum) {
            return self::FILE_ENUMS;
        }

        if ($entity instanceof PHPConstant) {
            return self::FILE_CONSTANTS;
        }

        throw new \InvalidArgumentException('Unknown entity type: ' . get_class($entity));
    }

    /**
     * Get file identifier from type name
     */
    public function getFileForTypeName(string $typeName): string
    {
        return match ($typeName) {
            'PHPClass' => self::FILE_CLASSES,
            'PHPFunction' => self::FILE_FUNCTIONS,
            'PHPInterface' => self::FILE_INTERFACES,
            'PHPEnum' => self::FILE_ENUMS,
            'PHPConstant' => self::FILE_CONSTANTS,
            default => throw new \InvalidArgumentException('Unknown type name: ' . $typeName),
        };
    }

    /**
     * Get all file identifiers
     */
    public function getAllFiles(): array
    {
        return [
            self::FILE_CLASSES,
            self::FILE_FUNCTIONS,
            self::FILE_INTERFACES,
            self::FILE_ENUMS,
            self::FILE_CONSTANTS,
        ];
    }

    /**
     * Build full file path for a given base path and file identifier
     */
    public function buildFilePath(string $basePath, string $fileIdentifier): string
    {
        $dir = dirname($basePath);
        $basename = basename($basePath, '.json');

        return $dir . '/' . $basename . $fileIdentifier . '.json';
    }
}
