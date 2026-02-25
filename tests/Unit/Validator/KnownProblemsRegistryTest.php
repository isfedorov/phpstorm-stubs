<?php

namespace StubTests\Unit\Validator;

use PHPUnit\Framework\TestCase;
use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;
use StubTests\Sources\Validator\KnownProblems\ProblemType;
use StubTests\Sources\Validator\KnownProblemsRegistry;

class KnownProblemsRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset singleton between tests
        KnownProblemsRegistry::reset();
    }

    protected function tearDown(): void
    {
        // Reset singleton after tests
        KnownProblemsRegistry::reset();
        parent::tearDown();
    }

    public function testSingletonInstance(): void
    {
        $instance1 = KnownProblemsRegistry::getInstance();
        $instance2 = KnownProblemsRegistry::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function testGetAllProblems(): void
    {
        $registry = KnownProblemsRegistry::getInstance();
        $problems = $registry->getAllProblems();

        $this->assertIsArray($problems);
        $this->assertNotEmpty($problems);
        $this->assertContainsOnlyInstancesOf(
            \StubTests\Sources\Validator\KnownProblems\ProblemDefinition::class,
            $problems
        );
    }

    public function testHasProblemForOverloadedFunction(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        // dba_fetch should have known problem for ParameterNamesCheck in PHP 8.0
        $hasProblem = $registry->hasProblem(
            'functions',
            '\\dba_fetch',
            'ParameterNamesCheck',
            '8.0'
        );

        $this->assertTrue($hasProblem);
    }

    public function testNoProblemForUnaffectedCheck(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        // dba_fetch should NOT have problem for ReturnTypesCheck
        $hasProblem = $registry->hasProblem(
            'functions',
            '\\dba_fetch',
            'ReturnTypesCheck',
            '8.0'
        );

        $this->assertFalse($hasProblem);
    }

    public function testNoProblemForNonExistentFunction(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        $hasProblem = $registry->hasProblem(
            'functions',
            '\\non_existent_function',
            'ParameterNamesCheck',
            '8.0'
        );

        $this->assertFalse($hasProblem);
    }

    public function testShouldSkipValidation(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        $shouldSkip = $registry->shouldSkipValidation(
            'functions',
            '\\dba_fetch',
            'ParameterNamesCheck',
            '8.0'
        );

        $this->assertTrue($shouldSkip);
    }

    public function testGetSkipReason(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        $reason = $registry->getSkipReason(
            'functions',
            '\\dba_fetch',
            'ParameterNamesCheck',
            '8.0'
        );

        $this->assertNotNull($reason);
        $this->assertStringContainsString('overload', strtolower($reason));
    }

    public function testVersionRangeFiltering(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        // Test version within range
        $hasProblemInRange = $registry->hasProblem(
            'functions',
            '\\dba_fetch',
            'ParameterNamesCheck',
            '8.0'
        );
        $this->assertTrue($hasProblemInRange, 'PHP 8.0 should be within affected range');

        // Test version at upper boundary
        $hasProblemAtBoundary = $registry->hasProblem(
            'functions',
            '\\dba_fetch',
            'ParameterNamesCheck',
            '8.4'
        );
        $this->assertTrue($hasProblemAtBoundary, 'PHP 8.4 should be within affected range');
    }

    public function testMultipleAffectedChecks(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        // dba_fetch affects both ParameterNamesCheck and ParameterTypesCheck
        $hasNamesProblem = $registry->hasProblem(
            'functions',
            '\\dba_fetch',
            'ParameterNamesCheck',
            '8.0'
        );

        $hasTypesProblem = $registry->hasProblem(
            'functions',
            '\\dba_fetch',
            'ParameterTypesCheck',
            '8.0'
        );

        $this->assertTrue($hasNamesProblem);
        $this->assertTrue($hasTypesProblem);
    }

    public function testResetClearsSingleton(): void
    {
        $instance1 = KnownProblemsRegistry::getInstance();

        KnownProblemsRegistry::reset();

        $instance2 = KnownProblemsRegistry::getInstance();

        $this->assertNotSame($instance1, $instance2);
    }

    public function testProblemsIndex(): void
    {
        $registry = KnownProblemsRegistry::getInstance();
        $index = $registry->getProblemsIndex();

        $this->assertIsArray($index);
        $this->assertArrayHasKey('functions', $index);
        $this->assertArrayHasKey('\\dba_fetch', $index['functions']);
    }

    public function testAllKnownProblemsAreAccessible(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        // Test a few known overloaded functions
        $knownFunctions = [
            '\\dba_fetch',
            '\\dba_open',
            '\\strtr',
            '\\setcookie',
            '\\session_set_save_handler',
        ];

        foreach ($knownFunctions as $function) {
            $hasProblem = $registry->hasProblem(
                'functions',
                $function,
                'ParameterNamesCheck',
                '8.0'
            );
            $this->assertTrue(
                $hasProblem,
                "Expected {$function} to have a known problem"
            );
        }
    }

    // ── CLASS_INTERFACES check ────────────────────────────────────────────────

    public function testHasProblemForClassInterfacesCheck(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        // SimpleXMLElement implements ArrayAccess at C level (visible in no PHP version via reflection)
        $this->assertTrue(
            $registry->hasProblem('classes', '\SimpleXMLElement', 'ClassInterfacesCheck', '8.0')
        );
    }

    public function testSimpleXmlElementSkippedAcrossAllVersions(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        foreach (['5.6', '7.0', '7.4', '8.0', '8.4'] as $version) {
            $this->assertTrue(
                $registry->hasProblem('classes', '\SimpleXMLElement', 'ClassInterfacesCheck', $version),
                "SimpleXMLElement should skip ClassInterfacesCheck for PHP {$version}"
            );
        }
    }

    public function testSplObjectStorageSkippedForLegacyVersionsNotPhp84(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        // SeekableIterator was added in PHP 8.4 — problem applies to 5.6–8.3
        foreach (['5.6', '7.0', '8.0', '8.3'] as $version) {
            $this->assertTrue(
                $registry->hasProblem('classes', '\SplObjectStorage', 'ClassInterfacesCheck', $version),
                "SplObjectStorage should skip ClassInterfacesCheck for PHP {$version}"
            );
        }

        $this->assertFalse(
            $registry->hasProblem('classes', '\SplObjectStorage', 'ClassInterfacesCheck', '8.4'),
            'SplObjectStorage should NOT skip ClassInterfacesCheck for PHP 8.4 (SeekableIterator is present)'
        );
    }

    public function testSplFileInfoSkippedForLegacyVersionsNotPhp80(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        // Stringable was added in PHP 8.0 — problem applies to 5.6–7.4
        foreach (['5.6', '7.0', '7.4'] as $version) {
            $this->assertTrue(
                $registry->hasProblem('classes', '\SplFileInfo', 'ClassInterfacesCheck', $version),
                "SplFileInfo should skip ClassInterfacesCheck for PHP {$version}"
            );
        }

        foreach (['8.0', '8.1', '8.4'] as $version) {
            $this->assertFalse(
                $registry->hasProblem('classes', '\SplFileInfo', 'ClassInterfacesCheck', $version),
                "SplFileInfo should NOT skip ClassInterfacesCheck for PHP {$version}"
            );
        }
    }

    public function testGetSkipReasonForClassProblemContainsDescription(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        $reason = $registry->getSkipReason('classes', '\SimpleXMLElement', 'ClassInterfacesCheck', '8.0');

        $this->assertNotNull($reason);
        $this->assertStringContainsString('ArrayAccess', $reason);
    }

    public function testUnknownCheckNameReturnsFalse(): void
    {
        $registry = KnownProblemsRegistry::getInstance();

        // Non-existent check name hits the default branch of stringToCheckType → null → false
        $this->assertFalse(
            $registry->hasProblem('functions', '\dba_fetch', 'NonExistentCheck', '8.0')
        );
    }

    public function testProblemsIndexContainsClassesKey(): void
    {
        $registry = KnownProblemsRegistry::getInstance();
        $index = $registry->getProblemsIndex();

        $this->assertArrayHasKey('classes', $index);
        $this->assertArrayHasKey('\SimpleXMLElement', $index['classes']);
    }

    // ── CLASS_METHODS_EXIST check ─────────────────────────────────────────────

    public function testClassMethodsExistCheckNameMapsCorrectly(): void
    {
        // Register a custom known problem for CheckType::CLASS_METHODS_EXIST and verify
        // that querying with the string 'ClassMethodsExistCheck' correctly resolves it.
        // This tests the 'ClassMethodsExistCheck' → CheckType::CLASS_METHODS_EXIST branch
        // of KnownProblemsRegistry::stringToCheckType().
        $provider = $this->createMock(KnownProblemsProvider::class);
        $provider->method('getProblems')->willReturn([
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\SomeSpecialClass',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_METHODS_EXIST],
                versionRange: new PhpVersionRange('8.0', '8.4'),
                reason: 'Test CLASS_METHODS_EXIST mapping'
            ),
        ]);

        KnownProblemsRegistry::reset();
        $registry = KnownProblemsRegistry::getInstance($provider);

        // Version within range → problem found
        $this->assertTrue(
            $registry->hasProblem('classes', '\SomeSpecialClass', 'ClassMethodsExistCheck', '8.0'),
            "'ClassMethodsExistCheck' should map to CheckType::CLASS_METHODS_EXIST"
        );

        // Version outside range → problem not found
        $this->assertFalse(
            $registry->hasProblem('classes', '\SomeSpecialClass', 'ClassMethodsExistCheck', '7.4'),
            "Version outside registered range should return false"
        );
    }
}
