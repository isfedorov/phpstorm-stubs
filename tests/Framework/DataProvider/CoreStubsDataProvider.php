<?php

namespace StubTests\Sources\DataProvider;

use RuntimeException;

/**
 * Data provider that filters stub files by category (Core, Bundled, External, PECL).
 * 
 * This provider is useful for tests that should only run against specific categories
 * of stubs, such as type hint validation which should only check core PHP functionality.
 */
class CoreStubsDataProvider implements StubsDataProvider
{
    /** @var StubCategory[] */
    private array $categories;
    private string $stubsRootPath;
    private array $excludedPaths = ['vendor', 'tests', '.git', '.idea'];
    private ?array $cachedStubFiles = null;

    /**
     * @param StubCategory|StubCategory[] $categories Single category or array of categories to include
     * @param string|null $stubsRootPath Optional custom root path for stubs
     */
    public function __construct(StubCategory|array $categories, ?string $stubsRootPath = null)
    {
        $this->categories = is_array($categories) ? $categories : [$categories];
        $this->stubsRootPath = $stubsRootPath ?? dirname(__DIR__, 3);
    }

    /**
     * Get all PHP stub files from directories matching the configured categories.
     *
     * @return array Array of absolute file paths to stub files
     */
    public function getAllStubFiles(): array
    {
        if ($this->cachedStubFiles !== null) {
            return $this->cachedStubFiles;
        }

        $stubFiles = [];
        $allowedDirectories = $this->getAllowedDirectories();

        // Scan root directory for matching directories
        $rootIterator = new \DirectoryIterator($this->stubsRootPath);
        
        foreach ($rootIterator as $item) {
            if ($item->isDot() || !$item->isDir()) {
                continue;
            }
            
            $dirName = $item->getFilename();
            
            // Skip excluded directories
            if (in_array($dirName, $this->excludedPaths, true)) {
                continue;
            }
            
            // Check if this directory matches our category filter
            if (!$this->isDirectoryAllowed($dirName, $allowedDirectories)) {
                continue;
            }
            
            // Recursively collect PHP files from this directory
            $stubFiles = array_merge($stubFiles, $this->collectPhpFiles($item->getPathname()));
        }

        $this->cachedStubFiles = $stubFiles;
        return $stubFiles;
    }

    /**
     * Get the content of a stub file.
     *
     * @param string $path Absolute or relative path to the stub file
     * @return string The content of the file
     * @throws RuntimeException If the file cannot be read
     */
    public function getStubFileContent(string $path): string
    {
        // If path is relative, make it absolute from stubs root
        if (!str_starts_with($path, '/')) {
            $path = $this->stubsRootPath . '/' . $path;
        }

        if (!file_exists($path)) {
            throw new RuntimeException("Stub file not found: {$path}");
        }

        if (!is_readable($path)) {
            throw new RuntimeException("Stub file not readable: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Failed to read stub file: {$path}");
        }

        return $content;
    }

    /**
     * Get the stubs root path.
     *
     * @return string The absolute path to the stubs root directory
     */
    public function getStubsRootPath(): string
    {
        return $this->stubsRootPath;
    }

    /**
     * Get the categories this provider is filtering for.
     *
     * @return StubCategory[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * Get all allowed directories based on configured categories.
     *
     * @return array Map of directory name => true for fast lookup
     */
    private function getAllowedDirectories(): array
    {
        $directories = [];
        
        foreach ($this->categories as $category) {
            foreach ($category->getDirectories() as $dir) {
                $directories[$dir] = true;
            }
        }
        
        return $directories;
    }

    /**
     * Check if a directory is allowed based on category filter.
     *
     * @param string $directoryName The directory name to check
     * @param array $allowedDirectories Map of allowed directories
     * @return bool
     */
    private function isDirectoryAllowed(string $directoryName, array $allowedDirectories): bool
    {
        // For PECL category, check if directory is NOT in any other category
        if (in_array(StubCategory::PECL, $this->categories, true)) {
            $isPecl = true;
            foreach ([StubCategory::CORE, StubCategory::BUNDLED, StubCategory::EXTERNAL] as $category) {
                if ($category->containsDirectory($directoryName)) {
                    $isPecl = false;
                    break;
                }
            }
            if ($isPecl) {
                return true;
            }
        }
        
        // For other categories, check explicit inclusion
        return isset($allowedDirectories[$directoryName]);
    }

    /**
     * Recursively collect all PHP files from a directory.
     *
     * @param string $directory The directory to scan
     * @return array Array of absolute file paths
     */
    private function collectPhpFiles(string $directory): array
    {
        $phpFiles = [];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getPathname();

                // Skip PhpStormStubsMap.php as it's generated
                if (basename($filePath) === 'PhpStormStubsMap.php') {
                    continue;
                }

                // Skip .phpstorm.meta.php files as they're IDE metadata, not stubs
                if (basename($filePath) === '.phpstorm.meta.php') {
                    continue;
                }

                $phpFiles[] = $filePath;
            }
        }

        return $phpFiles;
    }
}
