<?php

namespace StubTests\Sources\Validator\Enums;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\CheckInterface;
use StubTests\Sources\Validator\CheckResultSet;

class EnumNamespaceCheck implements CheckInterface
{
    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        $stubEnum = null;
        foreach ($stubs->getEnums() as $enum) {
            if ($enum->getId() === $entityId) {
                $stubEnum = $enum;
                break;
            }
        }

        if ($stubEnum === null) {
            $results->addFailure($entityId, "Enum {$entityId} not found in stubs");
            return $results;
        }

        $stubNamespace = method_exists($stubEnum, 'getNamespace') ? $stubEnum->getNamespace() : null;

        $lastBackslashPos = strrpos($entityId, '\\');
        if ($lastBackslashPos === false) {
            $expectedNamespace = null;
        } elseif ($lastBackslashPos === 0) {
            $expectedNamespace = '\\';
        } else {
            $expectedNamespace = substr($entityId, 0, $lastBackslashPos);
        }

        if ($stubNamespace !== $expectedNamespace) {
            $results->addFailure(
                $entityId,
                "Namespace mismatch for enum {$entityId}: expected '" .
                ($expectedNamespace ?? '(no namespace)') .
                "', found '" .
                ($stubNamespace ?? '(no namespace)') .
                "' in stubs"
            );
        } else {
            $results->addSuccess($entityId);
        }

        return $results;
    }
}
