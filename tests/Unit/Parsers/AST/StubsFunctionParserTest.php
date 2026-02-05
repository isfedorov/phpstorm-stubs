<?php

namespace StubTests\Unit\Parsers\AST;

use PHPUnit\Framework\TestCase as BaseTestCase;
use StubTests\Sources\DataProvider\StubsDataProvider;
use StubTests\Sources\Model\Entities\PHPFunction;
use StubTests\Sources\Model\Entities\PHPParameter;
use StubTests\Sources\Parsers\Entities\Stubs\StubFunctionParser;
use StubTests\Unit\Parsers\AST\fixtures\FixtureStubsDataProvider;

class StubsFunctionParserTest extends BaseTestCase
{
    private StubsDataProvider $filesProvider;
    private StubFunctionParser $parser;

    protected function setUp(): void
    {
        $fixturesPath = __DIR__ . '/fixtures/Functions';
        $this->filesProvider = new FixtureStubsDataProvider($fixturesPath);
        $this->parser = new StubFunctionParser();
    }

    public function testItReturnsCorrectInstance()
    {
        $stubCode = $this->filesProvider->getStubFileContent('simple_function.txt');
        $basePHPElement = $this->parser->parse($stubCode);
        self::assertInstanceOf(PHPFunction::class, $basePHPElement);
    }

    public function testItCanParseSimpleFunctionName()
    {
        $stubCode = $this->filesProvider->getStubFileContent('simple_function.txt');
        $function = $this->parser->parse($stubCode);
        self::assertEquals('strlen', $function->getName());
    }

    public function testItSetsRootNamespaceForFunctionWithoutNamespace()
    {
        $stubCode = $this->filesProvider->getStubFileContent('simple_function.txt');
        $function = $this->parser->parse($stubCode);
        self::assertEquals('\\', $function->getNamespace());
    }

    public function testItCanParseNamespace()
    {
        $stubCode = $this->filesProvider->getStubFileContent('complete_function.txt');
        $function = $this->parser->parse($stubCode);
        self::assertEquals('\\MyNamespace\\SubNamespace', $function->getNamespace());
    }

    public function testItCanParseFunctionName()
    {
        $stubCode = $this->filesProvider->getStubFileContent('complete_function.txt');
        $function = $this->parser->parse($stubCode);
        self::assertEquals('complexFunction', $function->getName());
    }

    public function testItCanParseId()
    {
        $stubCode = $this->filesProvider->getStubFileContent('complete_function.txt');
        $function = $this->parser->parse($stubCode);
        self::assertEquals('\\MyNamespace\\SubNamespace\\complexFunction', $function->getId());
    }

    public function testItCanParseIdWithRootNamespace()
    {
        $stubCode = $this->filesProvider->getStubFileContent('simple_function.txt');
        $function = $this->parser->parse($stubCode);
        self::assertEquals('\\strlen', $function->getId());
    }

    public function testItCanParseNonDeprecatedFunction()
    {
        $stubCode = $this->filesProvider->getStubFileContent('simple_function.txt');
        $function = $this->parser->parse($stubCode);
        self::assertFalse($function->isDeprecated());
    }

    public function testItCanParseDeprecatedFunction()
    {
        $stubCode = $this->filesProvider->getStubFileContent('complete_function.txt');
        $function = $this->parser->parse($stubCode);
        self::assertTrue($function->isDeprecated());
    }

    public function testItSetsEmptyArrayIfNoParametersInFunction()
    {
        $stubCode = '<?php function noParams(): void {}';
        $function = $this->parser->parse($stubCode);
        self::assertIsArray($function->getParameters());
        self::assertEmpty($function->getParameters());
    }

    public function testItCanParseParameters()
    {
        $stubCode = $this->filesProvider->getStubFileContent('complete_function.txt');
        $function = $this->parser->parse($stubCode);
        self::assertNotNull($function->getParameters());
        self::assertNotEmpty($function->getParameters());
        self::assertEquals(3, sizeof($function->getParameters()));
    }

    public function testItReturnsCorrectInstanceForParameter()
    {
        $stubCode = $this->filesProvider->getStubFileContent('complete_function.txt');
        $function = $this->parser->parse($stubCode);
        $parameters = $function->getParameters();
        self::assertInstanceOf(PHPParameter::class, $parameters[0]);
    }

    public function testItReturnsActuallyParsedParameters()
    {
        $stubCode = $this->filesProvider->getStubFileContent('complete_function.txt');
        $function = $this->parser->parse($stubCode);
        $parameters = $function->getParameters();
        self::assertEquals('param1', $parameters[0]->getName());
        self::assertEquals('param2', $parameters[1]->getName());
        self::assertEquals('param3', $parameters[2]->getName());
    }

    public function testItCanParseSimpleReturnType()
    {
        $stubCode = $this->filesProvider->getStubFileContent('simple_function.txt');
        $function = $this->parser->parse($stubCode);
        self::assertNotNull($function->getReturnTypeFromSignature());
        self::assertEquals('int', $function->getReturnTypeFromSignature());
    }

    public function testItCanParseUnionReturnType()
    {
        $stubCode = $this->filesProvider->getStubFileContent('complete_function.txt');
        $function = $this->parser->parse($stubCode);
        self::assertNotNull($function->getReturnTypeFromSignature());
        self::assertEquals('string|int|null', $function->getReturnTypeFromSignature());
    }

    public function testItCanParseFunctionWithSingleParameter()
    {
        $stubCode = $this->filesProvider->getStubFileContent('simple_function.txt');
        $function = $this->parser->parse($stubCode);
        self::assertEquals(1, sizeof($function->getParameters()));
        self::assertEquals('string', $function->getParameters()[0]->getName());
    }
}
