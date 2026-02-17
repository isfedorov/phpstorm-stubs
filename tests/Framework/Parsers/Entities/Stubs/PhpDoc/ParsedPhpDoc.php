<?php

namespace StubTests\Framework\Parsers\Entities\Stubs\PhpDoc;

/**
 * Value object representing parsed PhpDoc information.
 * Contains all extracted data from a PhpDoc comment block.
 */
class ParsedPhpDoc
{

    public ?string $rawPhpDoc = null;


    public ?string $returnType = null;

    public array $paramTypes = [];


    public ?string $varType = null;


    public ?string $sinceVersion = null;


    public ?string $removedVersion = null;


    public bool $isDeprecated = false;

    /**
     * Create a new ParsedPhpDoc instance.
     * All properties can be set via constructor for convenience.
     */
    public function __construct(
        ?string $rawPhpDoc = null,
        ?string $returnType = null,
        array $paramTypes = [],
        ?string $varType = null,
        ?string $sinceVersion = null,
        ?string $removedVersion = null,
        bool $isDeprecated = false
    ) {
        $this->rawPhpDoc = $rawPhpDoc;
        $this->returnType = $returnType;
        $this->paramTypes = $paramTypes;
        $this->varType = $varType;
        $this->sinceVersion = $sinceVersion;
        $this->removedVersion = $removedVersion;
        $this->isDeprecated = $isDeprecated;
    }
}
