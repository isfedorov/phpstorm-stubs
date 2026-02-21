<?php

namespace StubTests\Framework\Validator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\Runner;
use StubTests\Sources\Validator\CheckInterface;

/**
 * Abstract base class for validator tests.
 *
 * Provides common functionality for:
 * - Building data providers from PHP version ranges
 * - Loading reflection and stub data
 * - Running validation checks
 * - Asserting results
 */
abstract class ValidatorTestBase extends TestCase
{
    protected const ALL_PHP_VERSIONS = ['5.6', '7.0', '7.1', '7.2', '7.3', '7.4', '8.0', '8.1', '8.2', '8.3', '8.4'];

    /**
     * Generic data provider that yields [methodName, entityId, phpVersion] for each entity.
     *
     * Scans the test class methods for PhpVersionRange attributes and yields test cases
     * for each entity (class/function/interface/etc) in each supported PHP version.
     *
     * @return iterable<string, array{string, string, string}>
     */
    public static function entityProvider(): iterable
    {
        $reflector = new ReflectionClass(static::class);

        foreach ($reflector->getMethods() as $method) {
            // Skip methods that don't have PhpVersionRange attribute
            $attrs = $method->getAttributes(PhpVersionRange::class);
            if (empty($attrs)) {
                continue;
            }

            foreach ($attrs as $attr) {
                /** @var PhpVersionRange $range */
                $range = $attr->newInstance();

                foreach (self::ALL_PHP_VERSIONS as $version) {
                    if (!$range->includes($version)) {
                        continue;
                    }

                    $reflection = Runner::getReflection($version);

                    // Yield entities based on method name pattern
                    $entities = static::getEntitiesForMethod($method->getName(), $reflection);

                    foreach ($entities as $entity) {
                        $entityId = static::getEntityId($entity);
                        $testName = static::buildTestName($method->getName(), $entityId, $version);

                        yield $testName => [$method->getName(), $entityId, $version];
                    }
                }
            }
        }
    }

    #[DataProvider('entityProvider')]
    public function testEntity(string $methodName, string $entityId, string $phpVersion): void
    {
        if (!method_exists($this, $methodName)) {
            $this->fail("Method {$methodName} does not exist in " . static::class);
        }

        $this->$methodName($entityId, $phpVersion);
    }

    /**
     * Get entities to test based on the method name.
     * Subclasses can override this to filter entities.
     *
     * @param string $methodName
     * @param \StubTests\Sources\Parsers\ParsedDataStorageManager $reflection
     * @return iterable
     */
    protected static function getEntitiesForMethod(string $methodName, $reflection): iterable
    {
        // Default: return all entities from reflection
        // Subclasses should override to return specific entity types
        return [];
    }

    /**
     * Get the unique identifier for an entity.
     *
     * @param mixed $entity
     * @return string
     */
    protected static function getEntityId($entity): string
    {
        if (method_exists($entity, 'getId')) {
            return $entity->getId();
        }

        if (method_exists($entity, 'getName')) {
            return $entity->getName();
        }

        return (string) $entity;
    }

    /**
     * Build a unique test name from method, entity, and version.
     *
     * @param string $methodName
     * @param string $entityId
     * @param string $phpVersion
     * @return string
     */
    protected static function buildTestName(string $methodName, string $entityId, string $phpVersion): string
    {
        // Sanitize entity ID for test name (remove backslashes and special chars)
        $sanitizedId = str_replace(['\\', '/', ':', ' '], '_', $entityId);
        return "{$methodName}_{$sanitizedId}_{$phpVersion}";
    }

    /**
     * Execute a validation check and assert results.
     *
     * @param CheckInterface $check The validation check to run
     * @param string $entityId The entity identifier
     * @param string $phpVersion The PHP version
     * @param string|null $customMessage Optional custom assertion message
     */
    protected function executeCheck(
        CheckInterface $check,
        string $entityId,
        string $phpVersion,
        ?string $customMessage = null
    ): void {
        // Skip if check doesn't support this PHP version
        if (!$check->supports($phpVersion)) {
            $this->markTestSkipped(
                get_class($check) . " does not support PHP {$phpVersion}"
            );
        }

        $stubs = Runner::getStubs();
        $results = $check->run($stubs, $entityId, $phpVersion);

        $failures = $results->getFailures();
        $message = $customMessage ?? "PHP {$phpVersion}: Validation failed for {$entityId}";

        $this->assertEmpty(
            $failures,
            $message . "\n" . implode("\n", $failures)
        );
    }

    /**
     * Execute multiple validation checks and assert all pass.
     *
     * @param CheckInterface[] $checks Array of validation checks to run
     * @param string $entityId The entity identifier
     * @param string $phpVersion The PHP version
     */
    protected function executeChecks(
        array $checks,
        string $entityId,
        string $phpVersion
    ): void {
        foreach ($checks as $check) {
            $this->executeCheck($check, $entityId, $phpVersion);
        }
    }
}
