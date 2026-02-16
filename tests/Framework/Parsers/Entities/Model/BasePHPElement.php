<?php

namespace StubTests\Sources\Parsers\Entities\Model;

class BasePHPElement
{

    private ?string $name = null;
    private ?string $id = null;
    private ?string $sourcePath = null;
    private array $duplicates = [];
    protected ?string $phpDoc = null;
    protected ?string $sinceVersion = null;
    protected ?string $removedVersion = null;

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

    public function getPhpDoc(): ?string
    {
        return $this->phpDoc;
    }

    public function setPhpDoc(?string $phpDoc): void
    {
        $this->phpDoc = $phpDoc;
    }

    public function getSinceVersion(): ?string
    {
        return $this->sinceVersion;
    }

    public function setSinceVersion(?string $sinceVersion): void
    {
        $this->sinceVersion = $sinceVersion;
    }

    public function getRemovedVersion(): ?string
    {
        return $this->removedVersion;
    }

    public function setRemovedVersion(?string $removedVersion): void
    {
        $this->removedVersion = $removedVersion;
    }
}