<?php

namespace StubTests\Sources\Model\Entities;

class BasePHPElement
{

    private ?string $name = null;
    private ?string $id = null;
    private ?string $sourcePath = null;
    private array $duplicates = [];

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getSourcePath(): ?string
    {
        return $this->sourcePath;
    }

    public function setSourcePath(?string $sourcePath): void
    {
        $this->sourcePath = $sourcePath;
    }

    public function getDuplicates(): array
    {
        return $this->duplicates;
    }

    public function setDuplicates(array $duplicates): void
    {
        $this->duplicates = $duplicates;
    }

    public function addDuplicate(string $sourcePath): void
    {
        if (!in_array($sourcePath, $this->duplicates, true)) {
            $this->duplicates[] = $sourcePath;
        }
    }
}