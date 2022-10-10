<?php

namespace StubTests;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use StubTests\Parsers\StubParser;
use StubTests\TestData\Providers\Stubs\PhpCoreStubsProvider;
use StubTests\Tools\ModelAutoloader;
use function array_diff;
use function basename;
use function dirname;
use function is_dir;
use function is_string;
use function rmdir;
use function scandir;
use function strpos;
use function unlink;

require_once 'Tools/ModelAutoloader.php';
ModelAutoloader::register();

class DirectoryRemover
{
    public static function remove()
    {
        $stubsIterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(__DIR__ . '/../', FilesystemIterator::SKIP_DOTS)
            );
        $coreStubDirectories = PhpCoreStubsProvider::getCoreStubsDirectories();
        /** @var SplFileInfo $file */
        foreach ($stubsIterator as $file) {
            if (basename(dirname($file->getRealPath())) === 'phpstorm-stubs' ||
                strpos($file->getRealPath(), 'vendor') || strpos($file->getRealPath(), '.git') ||
                strpos($file->getRealPath(), 'tests') || strpos($file->getRealPath(), '.idea')) {
                continue;
            }
            if (!StubParser::stubBelongsToCore($file, $coreStubDirectories)) {
                self::deleteDirectory($file);
            }
        }
    }

    public static function deleteDirectory($path) {
        $path = is_string($path) ? $path : $path->getPath();
        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file){
            (is_dir("$path/$file")) ? self::deleteDirectory("$path/$file") : unlink("$path/$file");
        }
        return rmdir($path);
    }
}
DirectoryRemover::remove();
