<?php
declare(strict_types=1);

namespace StubTests\Parsers;

use Exception;
use FilesystemIterator;
use JsonException;
use LogicException;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use StubTests\Model\StubsContainer;
use StubTests\Parsers\Visitors\ASTVisitor;
use StubTests\Parsers\Visitors\CoreStubASTVisitor;
use StubTests\Parsers\Visitors\ParentConnector;
use StubTests\TestData\Providers\Stubs\PhpCoreStubsProvider;
use UnexpectedValueException;
use function basename;
use function dirname;
use function in_array;
use function strlen;

class StubParser
{
    private static ?StubsContainer $stubs = null;

    /**
     * @throws LogicException
     * @throws RuntimeException
     * @throws UnexpectedValueException
     * @throws JsonException
     */
    public static function getPhpStormStubs($shouldSuitCurrentPhpVersion): StubsContainer
    {
        self::$stubs = new StubsContainer($shouldSuitCurrentPhpVersion);
        $visitor = new ASTVisitor(self::$stubs, shouldSuitCurrentPhpVersion: $shouldSuitCurrentPhpVersion);
        $coreStubVisitor = new CoreStubASTVisitor(self::$stubs, $shouldSuitCurrentPhpVersion);
        self::processStubs(
            $visitor,
            $coreStubVisitor,
            fn (SplFileInfo $file): bool => $file->getFilename() !== '.phpstorm.meta.php'
        );
        self::convertStringInterfacesToObjects();
        $jsonData = json_decode(file_get_contents(__DIR__ . '/../TestData/mutedProblems.json'), false, 512, JSON_THROW_ON_ERROR);
        foreach (self::$stubs->getInterfaces() as $interface) {
            $interface->readMutedProblems($jsonData->interfaces);
        }
        foreach (self::$stubs->getClasses() as $class) {
            $class->readMutedProblems($jsonData->classes);
            $class->interfaces = CommonUtils::flattenArray($visitor->combineImplementedInterfaces($class), false);
            foreach ($class->methods as $method) {
                $method->templateTypes += $class->templateTypes;
            }
        }
        foreach (self::$stubs->getFunctions() as $function) {
            $function->readMutedProblems($jsonData->functions);
        }
        foreach (self::$stubs->getConstants() as $constant) {
            $constant->readMutedProblems($jsonData->constants);
        }
        return self::$stubs;
    }

    /**
     * @throws LogicException
     * @throws UnexpectedValueException
     */
    public static function processStubs(NodeVisitorAbstract $visitor, ?CoreStubASTVisitor $coreStubASTVisitor, callable $fileCondition): void
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $nameResolver = new NameResolver(null, ['preserveOriginalNames' => true]);

        $stubsIterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(__DIR__ . '/../../', FilesystemIterator::SKIP_DOTS)
            );
        $coreStubDirectories = PhpCoreStubsProvider::getCoreStubsDirectories();
        /** @var SplFileInfo $file */
        foreach ($stubsIterator as $file) {
            if (!$fileCondition($file) || basename(dirname($file->getRealPath())) === 'phpstorm-stubs' ||
                strpos($file->getRealPath(), 'vendor') || strpos($file->getRealPath(), '.git') ||
                strpos($file->getRealPath(), 'tests') || strpos($file->getRealPath(), '.idea')) {
                continue;
            }
            $code = file_get_contents($file->getRealPath());
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new ParentConnector());
            $traverser->addVisitor($nameResolver);
            if ($coreStubASTVisitor !== null && self::stubBelongsToCore($file, $coreStubDirectories)) {
                $coreStubASTVisitor->sourceFilePath = $file->getPath();
                $coreStubASTVisitor->sourceFileName = basename($file->getRealPath());
                $traverser->addVisitor($coreStubASTVisitor);
            } else {
                if ($visitor instanceof ASTVisitor) {
                    $visitor->sourceFilePath = $file->getPath();
                    $visitor->sourceFileName = basename($file->getRealPath());
                }
                $traverser->addVisitor($visitor);
            }
            $traverser->traverse($parser->parse($code, new StubsParserErrorHandler()));
        }
    }

    public static function stubBelongsToCore(SplFileInfo $file, array $coreStubDirectories): bool
    {
        $filePath = dirname($file->getRealPath());
        while (stripos($filePath, 'phpstorm-stubs') !== strlen($filePath) - strlen('phpstorm-stubs')) {
            if (in_array(basename($filePath), $coreStubDirectories, true)) {
                return true;
            }
            $filePath = dirname($filePath);
        }
        return false;
    }

    private static function convertStringInterfacesToObjects() {
        foreach (self::$stubs->getClasses() as $class) {
            $interfaceObjects = [];
            foreach ($class->interfaces as $interface) {
                try {
                    $interfaceObject = self::$stubs->getInterface($interface, shouldSuitCurrentPhpVersion: false);
                } catch (Exception $exception) {
                    $interfaceObject = self::$stubs->getInterface($interface, $class->sourceFilePath, shouldSuitCurrentPhpVersion: false);
                }
                $interfaceObjects[] = $interfaceObject;
            }
            $class->interfaces = $interfaceObjects;
        }
        foreach (self::$stubs->getInterfaces() as $interface) {
            $interfaceObjects = [];
            foreach ($interface->parentInterfaces as $interfaceAsString) {
                try {
                    $interfaceObject = self::$stubs->getInterface($interfaceAsString, shouldSuitCurrentPhpVersion: false);
                } catch (Exception $exception) {
                    $interfaceObject = self::$stubs->getInterface($interfaceAsString, $interface->sourceFilePath, false);
                }
                $interfaceObjects[] = $interfaceObject;
            }
            $interface->parentInterfaces = $interfaceObjects;
        }
    }
}
