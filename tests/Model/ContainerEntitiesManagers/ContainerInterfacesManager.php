<?php

namespace StubTests\Model\ContainerEntitiesManagers;

use RuntimeException;
use StubTests\Model\PHPInterface;
use StubTests\Parsers\ParserUtils;

class ContainerInterfacesManager
{
    /**
     * @var PHPInterface[]
     */
    private $interfaces = [];

    /**
     * @return PHPInterface[]
     */
    public function getInterfaces()
    {
        return $this->interfaces;
    }

    /**
     * @param string $id
     * @param string|null $sourceFilePath
     * @param bool $shouldSuitCurrentPhpVersion
     * @param false $fromReflection
     *
     * @return PHPInterface|null
     * @throws RuntimeException
     */
    public function getInterface($id, $sourceFilePath = null, $shouldSuitCurrentPhpVersion = true, $fromReflection = false)
    {
        if ($fromReflection) {
            $interfaces = array_filter($this->interfaces, function (PHPInterface $interface) use ($id) {
                return $interface->fqnBasedId === $id && $interface->getOrCreateStubSpecificProperties()->stubObjectHash == null;
            });
        } else {
            $interfaces = array_filter($this->interfaces, function (PHPInterface $interface) use ($shouldSuitCurrentPhpVersion, $id) {
                return $interface->fqnBasedId === $id && (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($interface));
            });
        }
        if (count($interfaces) === 1) {
            return array_pop($interfaces);
        }

        if ($sourceFilePath !== null) {
            $interfaces = array_filter($interfaces, function (PHPInterface $interface) use ($shouldSuitCurrentPhpVersion, $sourceFilePath) {
                return $interface->getOrCreateStubSpecificProperties()->sourceFilePath === $sourceFilePath &&
                    (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($interface));
            });
        }
        if (count($interfaces) > 1) {
            throw new RuntimeException("Multiple interfaces with name $id found");
        }
        if (!empty($interfaces)) {
            return array_pop($interfaces);
        }
        return null;
    }

    public function addInterface(PHPInterface $interface)
    {
        if (isset($interface->fqnBasedId)) {
            if (array_key_exists($interface->fqnBasedId, $this->interfaces)) {
                $amount = count(array_filter(
                    $this->interfaces,
                    function (PHPInterface $nextInterface) use ($interface) {
                        return $nextInterface->fqnBasedId === $interface->fqnBasedId;
                    }
                ));
                $this->interfaces[$interface->fqnBasedId . '_duplicated_' . $amount] = $interface;
            } else {
                $this->interfaces[$interface->fqnBasedId] = $interface;
            }
        }
    }
}