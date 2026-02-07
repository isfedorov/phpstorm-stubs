<?php

namespace StubTests\Sources\DataProvider;

interface ReflectionDataProvider
{
    /**
     * @return \ReflectionClass[]
     */
    public function getReflectionClasses();

    /**
     * @return \ReflectionClass[]
     */
    public function getReflectionInterfaces();

    /**
     * @return \ReflectionClass[]
     */
    public function getReflectionEnums();

    /**
     * @return \ReflectionFunction[]
     */
    public function getReflectionFunctions();

    /**
     * @return \ReflectionConstant[]
     */
    public function getReflectionConstants();
}