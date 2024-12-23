<?php

namespace StubTests\Model\ContainerEntitiesManagers;

use StubTests\Model\PHPEnum;
use StubTests\Parsers\ParserUtils;

class ContainerEnumsManager
{

    /**
     * @var PHPEnum[]
     */
    private $enums = [];


    /**
     * @param string $id
     * @param string|null $sourceFilePath
     * @param bool $shouldSuitCurrentPhpVersion
     * @param true $useFQNAsName
     * @param false $fromReflection
     *
     * @return PHPEnum|null
     * @throws RuntimeException
     */
    public function getEnum($id, $sourceFilePath = null, $shouldSuitCurrentPhpVersion = true, $fromReflection = false)
    {
        $enums = array_filter($this->enums, function (PHPEnum $enum) use ($shouldSuitCurrentPhpVersion, $id) {
            return $enum->fqnBasedId === $id && (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($enum));
        });
        if (count($enums) === 1) {
            return array_pop($enums);
        }

        if ($sourceFilePath !== null) {
            $enums = array_filter($enums, function (PHPEnum $enum) use ($shouldSuitCurrentPhpVersion, $sourceFilePath) {
                return $enum->getOrCreateStubSpecificProperties()->sourceFilePath === $sourceFilePath &&
                    (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($enum));
            });
        }
        if (count($enums) > 1) {
            throw new RuntimeException("Multiple enums with name $id found");
        }
        if (!empty($enums)) {
            return array_pop($enums);
        }
        return null;
    }

    public function getEnumByHash(string $hash)
    {
        $enums = array_filter($this->enums, function (PHPEnum $class) use ($hash) {
            return $class->getOrCreateStubSpecificProperties()->stubObjectHash === $hash;
        });
        if (count($enums) > 1) {
            throw new RuntimeException("Multiple enums with name $hash found");
        }
        return array_pop($enums);
    }



    /**
     * @return PHPEnum[]
     */
    public function getEnums()
    {
        return $this->enums;
    }


    public function getCoreEnums($shouldSuitCurrentPhpVersion = true)
    {
        return array_filter($this->enums, function (PHPEnum $enum) use ($shouldSuitCurrentPhpVersion) {
            return $enum->getOrCreateStubSpecificProperties()->stubBelongsToCore === true && (!$shouldSuitCurrentPhpVersion || ParserUtils::entitySuitsCurrentPhpVersion($enum));
        });
    }

    public function addEnum(PHPEnum $enum)
    {
        if (isset($enum->fqnBasedId)) {
            if (array_key_exists($enum->fqnBasedId, $this->enums)) {
                $amount = count(array_filter(
                    $this->enums,
                    function (PHPEnum $nextEnum) use ($enum) {
                        return $nextEnum->fqnBasedId === $enum->fqnBasedId;
                    }
                ));
                $this->enums[$enum->fqnBasedId . '_duplicated_' . $amount] = $enum;
            } else {
                $this->enums[$enum->fqnBasedId] = $enum;
            }
        }
    }
}
