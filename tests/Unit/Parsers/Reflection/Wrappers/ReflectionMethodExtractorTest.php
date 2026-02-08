<?php

namespace StubTests\Unit\Parsers\Reflection\Wrappers;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\ReflectionMethodExtractor;
use StubTests\Sources\Parsers\Entities\Reflection\Wrappers\AdaptedReflectionClass;

class ReflectionMethodExtractorTest extends TestCase
{
    // extractData() tests

    public function testItExtractsMethodsWithCorrectPrefixes()
    {
        $mockObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getName', 'isActive', 'hasItems', 'getCount'])->getMock();
        $mockObject->method('getName')->willReturn('TestName');
        $mockObject->method('isActive')->willReturn(true);
        $mockObject->method('hasItems')->willReturn(false);
        $mockObject->method('getCount')->willReturn(5);

        $config = ['methodPrefixes' => ['is', 'has', 'get']];
        $result = ReflectionMethodExtractor::extractData($mockObject, $config);

        self::assertArrayHasKey('getName', $result);
        self::assertArrayHasKey('isActive', $result);
        self::assertArrayHasKey('hasItems', $result);
        self::assertArrayHasKey('getCount', $result);
        self::assertEquals('TestName', $result['getName']);
        self::assertEquals(true, $result['isActive']);
        self::assertEquals(false, $result['hasItems']);
        self::assertEquals(5, $result['getCount']);
    }

    public function testItExtractsMethodsWithAllConfiguredPrefixes()
    {
        $mockObject = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['allowsNull', 'canExecute', 'inNamespace', 'returnsReference'])
            ->getMock();
        $mockObject->method('allowsNull')->willReturn(true);
        $mockObject->method('canExecute')->willReturn(false);
        $mockObject->method('inNamespace')->willReturn(true);
        $mockObject->method('returnsReference')->willReturn(false);

        $config = ['methodPrefixes' => ['allows', 'can', 'in', 'returns']];
        $result = ReflectionMethodExtractor::extractData($mockObject, $config);

        self::assertArrayHasKey('allowsNull', $result);
        self::assertArrayHasKey('canExecute', $result);
        self::assertArrayHasKey('inNamespace', $result);
        self::assertArrayHasKey('returnsReference', $result);
    }

    public function testItSkipsMethodsRequiringParameters()
    {
        // Use a real ReflectionClass - it has methods with/without params
        $reflection = new \ReflectionClass(\DateTime::class);
        $config = ['methodPrefixes' => ['get']];
        $result = ReflectionMethodExtractor::extractData($reflection, $config);

        // ReflectionClass methods without required parameters should be extracted
        self::assertArrayHasKey('getName', $result);
        self::assertArrayHasKey('getFileName', $result);
        self::assertArrayHasKey('getStartLine', $result);
        self::assertArrayHasKey('getEndLine', $result);

        // Methods requiring parameters should not be extracted
        // (getMethod, getProperty, getStaticPropertyValue all require params)
        self::assertArrayNotHasKey('getMethod', $result);
        self::assertArrayNotHasKey('getProperty', $result);
        self::assertArrayNotHasKey('getStaticPropertyValue', $result);
    }

    public function testItSkipsMagicMethods()
    {
        $reflection = new \ReflectionClass(\stdClass::class);
        $result = ReflectionMethodExtractor::extractData($reflection, []);

        // Should not extract __construct, __destruct, __toString, etc.
        self::assertArrayNotHasKey('__construct', $result);
        self::assertArrayNotHasKey('__toString', $result);
    }

    public function testItRespectsSkipMethodsConfiguration()
    {
        $mockObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getName', 'getValue'])->getMock();
        $mockObject->method('getName')->willReturn('TestName');
        $mockObject->method('getValue')->willReturn('TestValue');

        $config = [
            'methodPrefixes' => ['get'],
            'skipMethods' => ['getValue']
        ];
        $result = ReflectionMethodExtractor::extractData($mockObject, $config);

        self::assertArrayHasKey('getName', $result);
        self::assertArrayNotHasKey('getValue', $result);
    }

    public function testItHandlesCustomHandlers()
    {
        $mockObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getName'])->getMock();
        $mockObject->method('getName')->willReturn('OriginalName');

        $config = [
            'methodPrefixes' => ['get'],
            'customHandlers' => [
                'getName' => function ($obj, $methodName) {
                    return 'CustomHandled: ' . $obj->getName();
                }
            ]
        ];
        $result = ReflectionMethodExtractor::extractData($mockObject, $config);

        self::assertEquals('CustomHandled: OriginalName', $result['getName']);
    }

    public function testItCatchesExceptionsGracefully()
    {
        $mockObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getName', 'getError'])->getMock();
        $mockObject->method('getName')->willReturn('ValidName');
        $mockObject->method('getError')->willThrowException(new \Exception('Test exception'));

        $config = ['methodPrefixes' => ['get']];
        $result = ReflectionMethodExtractor::extractData($mockObject, $config);

        // getName should be extracted
        self::assertArrayHasKey('getName', $result);
        // getError should be skipped due to exception
        self::assertArrayNotHasKey('getError', $result);
    }

    // makeSerializable() tests

    public function testItHandlesNullAndFalseCorrectly()
    {
        self::assertNull(ReflectionMethodExtractor::makeSerializable(null));
        self::assertFalse(ReflectionMethodExtractor::makeSerializable(false));
    }

    public function testItHandlesPrimitives()
    {
        self::assertEquals('test', ReflectionMethodExtractor::makeSerializable('test'));
        self::assertEquals(42, ReflectionMethodExtractor::makeSerializable(42));
        self::assertEquals(3.14, ReflectionMethodExtractor::makeSerializable(3.14));
        self::assertTrue(ReflectionMethodExtractor::makeSerializable(true));
    }

    public function testItRecursivelyProcessesArrays()
    {
        $input = [
            'name' => 'test',
            'count' => 5,
            'nested' => ['a' => 1, 'b' => 2]
        ];
        $result = ReflectionMethodExtractor::makeSerializable($input);

        self::assertEquals($input, $result);
        self::assertIsArray($result);
        self::assertIsArray($result['nested']);
    }

    public function testItWrapsReflectionClassToAdaptedReflectionClass()
    {
        $reflectionClass = new \ReflectionClass(\stdClass::class);
        $result = ReflectionMethodExtractor::makeSerializable($reflectionClass);

        self::assertInstanceOf(AdaptedReflectionClass::class, $result);
    }

    public function testItPreventsInfiniteRecursion()
    {
        // Create deeply nested array
        $deep = ['level' => 0];
        $current = &$deep;
        for ($i = 1; $i < 10; $i++) {
            $current['nested'] = ['level' => $i];
            $current = &$current['nested'];
        }

        // Should not throw due to max depth limit
        $result = ReflectionMethodExtractor::makeSerializable($deep, 0, 3);
        self::assertIsArray($result);
    }

    public function testItReturnsAdaptedReflectionObjectsAsIs()
    {
        $reflectionClass = new \ReflectionClass(\stdClass::class);
        $adapted = new AdaptedReflectionClass($reflectionClass);

        $result = ReflectionMethodExtractor::makeSerializable($adapted);

        self::assertSame($adapted, $result);
    }

    public function testItConvertsObjectsWithToStringToString()
    {
        $obj = new class {
            public function __toString()
            {
                return 'StringRepresentation';
            }
        };

        $result = ReflectionMethodExtractor::makeSerializable($obj);
        self::assertEquals('StringRepresentation', $result);
    }

    public function testItReturnsClassNameForNonSerializableObjects()
    {
        $obj = new \stdClass();
        $result = ReflectionMethodExtractor::makeSerializable($obj);

        self::assertEquals('stdClass', $result);
    }

    // getPropertyKey() tests

    public function testGetPropertyKeyReturnsMethodNameAsIs()
    {
        self::assertEquals('isAbstract', ReflectionMethodExtractor::getPropertyKey('isAbstract'));
        self::assertEquals('getName', ReflectionMethodExtractor::getPropertyKey('getName'));
        self::assertEquals('hasMethod', ReflectionMethodExtractor::getPropertyKey('hasMethod'));
    }
}
