<?php

namespace StubTests\Sources\Parsers\Processors;

use StubTests\Sources\Model\Entities\PHPClass;
use StubTests\Sources\Parsers\EntityProcessor;

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
