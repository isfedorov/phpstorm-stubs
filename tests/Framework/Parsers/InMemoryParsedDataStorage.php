<?php

namespace StubTests\Sources\Parsers;

use StubTests\Sources\Parsers\Entities\Model\PHPClass;
use StubTests\Sources\Parsers\Entities\Model\PHPConstant;
use StubTests\Sources\Parsers\Entities\Model\PHPEnum;
use StubTests\Sources\Parsers\Entities\Model\PHPFunction;
use StubTests\Sources\Parsers\Entities\Model\PHPInterface;

class InMemoryParsedDataStorage implements ParsedDataStorageProvider
{
    private array $entities = [];
    private array $classes = [];
    private array $functions = [];
    private array $interfaces = [];
    private array $traits = [];
    private array $constants = [];
    private array $enums = [];

    public function __construct()
    {
    }


    public function getEntities()
    {
        return $this->entities;
    }

    public function addEntity($entity)
    {
        $this->entities[] = $entity;

        // Also categorize by type for faster lookups
        if ($entity instanceof PHPClass) {
            $this->classes[] = $entity;
        } elseif ($entity instanceof PHPFunction) {
            $this->functions[] = $entity;
        } elseif ($entity instanceof PHPInterface) {
            $this->interfaces[] = $entity;
        } elseif ($entity instanceof PHPEnum) {
            $this->enums[] = $entity;
        } elseif ($entity instanceof PHPConstant) {
            $this->constants[] = $entity;
        }
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    public function getEnums(): array
    {
        return $this->enums;
    }

    public function getConstants(): array
    {
        return $this->constants;
    }

}