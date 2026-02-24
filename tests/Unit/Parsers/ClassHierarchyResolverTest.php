<?php

namespace StubTests\Unit\Parsers;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Parsers\ClassAncestorNamesExtractor;
use StubTests\Sources\Parsers\ClassHierarchyResolver;
use StubTests\Sources\Parsers\Entities\Model\PHPClass;

class ClassHierarchyResolverTest extends TestCase
{
    private ClassHierarchyResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ClassHierarchyResolver();
    }

    private function makeClass(string $id, ?string $parentShortName = null): PHPClass
    {
        $parts = explode('\\', ltrim($id, '\\'));
        $shortName = array_pop($parts);
        $ns = empty($parts) ? '\\' : '\\' . implode('\\', $parts);

        $class = new PHPClass();
        $class->setId($id);
        $class->setName($shortName);
        $class->setNamespace($ns);

        if ($parentShortName !== null) {
            $stub = new PHPClass();
            $stub->setName($parentShortName);
            $class->parentClass = $stub;
        }

        return $class;
    }

    public function testRootNamespaceParentIsLinked(): void
    {
        $exception = $this->makeClass('\\Exception');
        $errorException = $this->makeClass('\\ErrorException', 'Exception');

        $this->resolver->resolve([$exception, $errorException]);

        $this->assertSame($exception, $errorException->parentClass);
    }

    public function testSameNamespaceShortNameIsLinkedViaNsFallback(): void
    {
        // BrokenRandomEngineError extends RandomError (short name in cache)
        // Both are in \Random namespace.
        $randomError = $this->makeClass('\\Random\\RandomError');
        $broken = $this->makeClass('\\Random\\BrokenRandomEngineError', 'RandomError');

        $this->resolver->resolve([$randomError, $broken]);

        $this->assertSame($randomError, $broken->parentClass);
    }

    public function testShortNameCollisionResolvesToCorrectFqnClass(): void
    {
        // Simulates the \ErrorException case: 16+ classes named "Exception" exist,
        // but \ErrorException should be linked to \Exception, not Swoole\...\Exception.
        $rootException = $this->makeClass('\\Exception');
        $swooleException = $this->makeClass('\\Swoole\\Coroutine\\Http2\\Client\\Exception', 'Exception');
        $errorException = $this->makeClass('\\ErrorException', 'Exception');

        $this->resolver->resolve([$rootException, $swooleException, $errorException]);

        // \ErrorException (in root namespace) must resolve to \Exception, not the Swoole one
        $this->assertSame($rootException, $errorException->parentClass,
            'ErrorException.parentClass must link to \\Exception, not a same-short-name class in another namespace');

        // Swoole's Exception's parent stub (short name "Exception") also resolves to root \Exception
        $this->assertSame($rootException, $swooleException->parentClass);
    }

    public function testAncestorChainAfterResolution(): void
    {
        // ErrorException -> Exception -> (none)
        // After resolution, ClassAncestorNamesExtractor should return ["Exception"]
        $exception = $this->makeClass('\\Exception');
        $errorException = $this->makeClass('\\ErrorException', 'Exception');

        $this->resolver->resolve([$exception, $errorException]);

        $extractor = new ClassAncestorNamesExtractor();
        $this->assertEquals(['Exception'], $extractor->extract($errorException));
    }

    public function testNamespacedAncestorChainAfterResolution(): void
    {
        // \Random\BrokenRandomEngineError -> RandomError -> (none)
        // After resolution via namespace fallback, extractor uses getId()
        // and returns "Random\RandomError" (without leading \), matching reflection format.
        $randomError = $this->makeClass('\\Random\\RandomError');
        $broken = $this->makeClass('\\Random\\BrokenRandomEngineError', 'RandomError');

        $this->resolver->resolve([$randomError, $broken]);

        $extractor = new ClassAncestorNamesExtractor();
        $this->assertEquals(['Random\\RandomError'], $extractor->extract($broken));
    }
}
