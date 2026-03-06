<?php

namespace StubTests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use StubTests\Sources\DataProvider\StubCategory;

/**
 * Validates the structural integrity of the stubs directory layout.
 *
 * Ensures every top-level stubs directory is explicitly registered in
 * StubCategory::getDirectories() so that new extension folders are never
 * silently ignored by data providers and category-based filters.
 */
class StubsStructureValidatorTest extends TestCase
{
    /**
     * Top-level directories in the stubs root that are not PHP extension stubs.
     * These are excluded from category-membership validation.
     */
    private const NON_STUBS_DIRS = ['vendor', 'tests', 'scripts', 'docker'];

    /**
     * Provides each top-level stubs directory as a separate test case.
     *
     * Scans the project root and yields every directory that:
     * - is not hidden (does not start with '.')
     * - is not a known non-stubs directory (vendor, tests, scripts, docker)
     *
     * @return iterable<string, array{string}>
     */
    public static function stubsDirectoryProvider(): iterable
    {
        $root = dirname(__DIR__);

        foreach (scandir($root) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (str_starts_with($entry, '.')) {
                continue; // skip hidden dirs (.git, .idea, .github, etc.)
            }
            if (!is_dir($root . '/' . $entry)) {
                continue;
            }
            if (in_array($entry, self::NON_STUBS_DIRS, true)) {
                continue;
            }

            yield $entry => [$entry];
        }
    }

    /**
     * Assert that every top-level stubs directory is present in StubCategory::getDirectories().
     *
     * When a new extension directory is added to the stubs root it must also be registered
     * in the appropriate StubCategory (CORE, BUNDLED, EXTERNAL, or PECL) so that
     * category-based data providers include it correctly.
     *
     * @param string $directoryName Top-level directory name (basename only, no path)
     */
    #[Test]
    #[DataProvider('stubsDirectoryProvider')]
    public function checkStubsDirectoriesExistsInMap(string $directoryName): void
    {
        $allCategoryDirs = [];
        foreach (StubCategory::cases() as $category) {
            foreach ($category->getDirectories() as $dir) {
                $allCategoryDirs[] = $dir;
            }
        }

        self::assertContains(
            $directoryName,
            $allCategoryDirs,
            "Directory '$directoryName' is not listed in any StubCategory::getDirectories(). " .
            "Add it to the appropriate category (CORE, BUNDLED, EXTERNAL, or PECL) in " .
            "tests/Framework/DataProvider/StubCategory.php."
        );
    }
}
