<?php

namespace StubTests\Sources\Validator\Interfaces;

use StubTests\Sources\Parsers\ParsedDataStorageManager;
use StubTests\Sources\Validator\CheckInterface;
use StubTests\Sources\Validator\CheckResultSet;

class InterfaceNamespaceCheck implements CheckInterface
{
    public function supports(string $phpVersion): bool
    {
        return true;
    }

    public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
    {
        $results = new CheckResultSet();

        $stubInterface = null;
        foreach ($stubs->getInterfaces() as $interface) {
            if ($interface->getId() === $entityId) {
                $stubInterface = $interface;
                break;
            }
        }

        if ($stubInterface === null) {
            $results->addFailure($entityId, "Interface {$entityId} not found in stubs");
            return $results;
        }

        $stubNamespace = method_exists($stubInterface, 'getNamespace') ? $stubInterface->getNamespace() : null;

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
                "Namespace mismatch for interface {$entityId}: expected '" .
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
