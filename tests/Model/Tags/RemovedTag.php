<?php

namespace StubTests\Model\Tags;

use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
use phpDocumentor\Reflection\Types\Context;

class RemovedTag extends BaseTag
{
    private ?string $version;

    public function __construct(RemovedTagAttributes $attributes)
    {
        $this->version = $attributes->version;
        $this->name = $attributes->name;
        $this->description = $attributes->description;
    }

    public static function create(?string $body, ?DescriptionFactory $descriptionFactory = null, ?Context $context = null): RemovedTag
    {
        if ($descriptionFactory !== null && !empty($tagBody)) {
            $removedTagParser = new RemovedTagParser();
            $removedTagParser->parse($tagBody);
            return self::createTagWithNotEmptyBody($removedTagParser, $tagBody, $descriptionFactory, $context);
        }
        return new self(new RemovedTagAttributes());
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function __toString(): string
    {
        return "PhpStorm internal '@removed' tag";
    }

    private static function createTagWithNotEmptyBody(RemovedTagParser $removedTagParser, string $body, DescriptionFactory $descriptionFactory, ?Context $context): RemovedTag
    {
        if ($removedTagParser->tagDoesNotContainVersion()) {
            $tagAttributes = new RemovedTagAttributes(description: $descriptionFactory->create($body, $context));

        } else {
            $tagAttributes = new RemovedTagAttributes($removedTagParser->version, $descriptionFactory->create($removedTagParser->description, $context));
        }
        return new self($tagAttributes);
    }
}
