<?php

namespace StubTests\Model;

use StubTests\Model\ContainerEntitiesManagers\ContainerClassesManager;
use StubTests\Model\ContainerEntitiesManagers\ContainerConstantsManager;
use StubTests\Model\ContainerEntitiesManagers\ContainerEnumsManager;
use StubTests\Model\ContainerEntitiesManagers\ContainerFunctionsManager;
use StubTests\Model\ContainerEntitiesManagers\ContainerInterfacesManager;

class StubsContainer
{
    private $containsReflectionStubs;

    /** @var ContainerConstantsManager */
    private $constantsManager;

    /** @var ContainerFunctionsManager */
    private $functionsManager;

    /** @var ContainerClassesManager */
    private $classesManager;

    /** @var ContainerInterfacesManager */
    private $interfacesManager;

    /** @var ContainerEnumsManager */
    private $enumsManager;


    public function __construct($forReflectionStubs)
    {
        $this->containsReflectionStubs = $forReflectionStubs;
        $this->constantsManager = new ContainerConstantsManager();
        $this->functionsManager = new ContainerFunctionsManager();
        $this->classesManager = new ContainerClassesManager();
        $this->interfacesManager = new ContainerInterfacesManager();
        $this->enumsManager = new ContainerEnumsManager();
    }

    public function getContainsReflectionStubs()
    {
        return $this->containsReflectionStubs;
    }

    public function getConstantsManager(): ContainerConstantsManager
    {
        return $this->constantsManager;
    }

    public function getFunctionsManager(): ContainerFunctionsManager
    {
        return $this->functionsManager;
    }

    public function getClassesManager(): ContainerClassesManager
    {
        return $this->classesManager;
    }

    public function getInterfacesManager(): ContainerInterfacesManager
    {
        return $this->interfacesManager;
    }

    public function getEnumsManager(): ContainerEnumsManager
    {
        return $this->enumsManager;
    }
}
