<?php

namespace StubTests\Sources\Parsers\Processors;

use StubTests\Framework\Parsers\Processors\EntityProcessor;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;

class IdGeneratorProcessor implements EntityProcessor
{
    public function process($entity, array $context = [])
    {
        if ($entity instanceof PHPClass && !$entity->getId()) {
            $entity->setId(uniqid('class_', true));
        }

        return $entity;
    }
}
