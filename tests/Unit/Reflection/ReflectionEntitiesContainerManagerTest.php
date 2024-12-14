<?php

namespace StubTests\Unit\Reflection;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Model\Entities\PHPFunction;
use StubTests\Sources\Model\EntitiesManagers\ReflectionEntitiesContainerManager;
use StubTests\Sources\Model\Predicats\GeneralEntities\FunctionsFilterPredicateProvider;
use StubTests\Sources\Model\StubsContainer;
use StubTests\Sources\Parsers\Entities\Reflection\ReflectionObjectFunctionParser;

class ReflectionEntitiesContainerManagerTest extends TestCase
{
    public function testItCanAddAFunctionToContainer()
    {
        $functionToAdd = new PHPFunction();
        $functionToAdd->name = 'foo';
        $functionToAdd->namespace = 'MyNameSpace';
        $functionToAdd->id = '\MyNameSpace\foo';
        $functionParserMock = $this->getMockBuilder(ReflectionObjectFunctionParser::class)->disableOriginalConstructor()->getMock();
        $functionParserMock->method('parse')->willReturn($functionToAdd);
        $container = new StubsContainer();
        $containerManager = new ReflectionEntitiesContainerManager($container);
        $containerManager->addFunction($functionToAdd);
        self::assertNotEmpty($container->getFunctions());
        self::assertNotNull($container->getFunctionWithId($functionToAdd->id));
        self::assertEquals($functionToAdd, $container->getFunctionWithId($functionToAdd->id));
    }
}