<?php

namespace StubTests\Sources\Parsers\Processors;

use StubTests\Sources\Parsers\EntityProcessor;

class NamespaceNormalizer implements EntityProcessor
{
    public function process($entity, array $context = [])
    {
        if (method_exists($entity, 'getNamespace') &&
            method_exists($entity, 'setNamespace')) {

            try {
                $ns = $entity->getNamespace();
                if ($ns !== null) {
                    // Remove leading backslash
                    $entity->setNamespace(ltrim($ns, '\\'));
                }
            } catch (\Error $e) {
                // Namespace not initialized, skip normalization
            }
        }

        return $entity;
    }
}
