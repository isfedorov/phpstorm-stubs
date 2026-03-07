<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Validator\KnownProblems\CheckType;
use StubTests\Sources\Validator\KnownProblems\DefaultKnownProblemsProvider;
use StubTests\Sources\Validator\KnownProblems\EntityType;
use StubTests\Sources\Validator\KnownProblems\KnownProblemsProvider;
use StubTests\Sources\Validator\KnownProblems\ProblemDefinition;

/**
 * Registry for known validation problems in stubs.
 *
 * Some PHP entities (functions, methods) have known issues where stubs
 * cannot perfectly match reflection data. Common cases:
 * - Overloaded function signatures (multiple valid signatures, reflection returns only one)
 * - Version-specific parameter changes
 * - Internal implementation details that differ from public API
 *
 * This registry uses a Provider pattern to load problems from type-safe PHP code,
 * providing compile-time validation and IDE support.
 */
class KnownProblemsRegistry
{
    private static ?KnownProblemsRegistry $instance = null;

    /** @var array<string, array<string, ProblemDefinition[]>> Indexed by entityType => entityId => problems */
    private array $problemsIndex = [];

    private function __construct(
        private KnownProblemsProvider $provider
    ) {
        $this->indexProblems();
    }

    /**
     * Get singleton instance of the registry.
     *
     * @param KnownProblemsProvider|null $provider Optional custom provider (mainly for testing)
     * @return self
     */
    public static function getInstance(?KnownProblemsProvider $provider = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($provider ?? new DefaultKnownProblemsProvider());
        }
        return self::$instance;
    }

    /**
     * Index all problems for fast lookup.
     *
     * When a problem has a non-empty $entityIds list, each ID in that list
     * is indexed separately (pointing to the same ProblemDefinition object).
     * Otherwise the single $entityId is used.
     */
    private function indexProblems(): void
    {
        foreach ($this->provider->getProblems() as $problem) {
            $entityTypeKey = $problem->entityType->value;
            $idsToIndex = !empty($problem->entityIds) ? $problem->entityIds : [$problem->entityId];

            if (!isset($this->problemsIndex[$entityTypeKey])) {
                $this->problemsIndex[$entityTypeKey] = [];
            }

            foreach ($idsToIndex as $entityId) {
                if (!isset($this->problemsIndex[$entityTypeKey][$entityId])) {
                    $this->problemsIndex[$entityTypeKey][$entityId] = [];
                }
                $this->problemsIndex[$entityTypeKey][$entityId][] = $problem;
            }
        }
    }

    /**
     * Check if an entity has a known problem for a specific check and PHP version.
     *
     * @param string $entityType Entity type: 'functions', 'methods', 'classes'
     * @param string $entityId Fully qualified entity ID (e.g., '\dba_fetch' or 'DateTime::format')
     * @param string $checkName Name of the check class (e.g., 'ParameterNamesCheck')
     * @param string $phpVersion PHP version being tested (e.g., '8.0')
     * @return bool True if entity has a known problem for this check and version
     */
    public function hasProblem(
        string $entityType,
        string $entityId,
        string $checkName,
        string $phpVersion
    ): bool {
        return $this->getProblemDefinition($entityType, $entityId, $checkName, $phpVersion) !== null;
    }

    /**
     * Get problem definition for an entity.
     *
     * @param string $entityType Entity type: 'functions', 'methods', 'classes'
     * @param string $entityId Fully qualified entity ID
     * @param string $checkName Name of the check class
     * @param string $phpVersion PHP version being tested
     * @return ProblemDefinition|null Problem definition or null if no problem exists
     */
    private function getProblemDefinition(
        string $entityType,
        string $entityId,
        string $checkName,
        string $phpVersion
    ): ?ProblemDefinition {
        $problems = $this->problemsIndex[$entityType][$entityId] ?? [];

        // Convert check name string to CheckType enum
        $checkType = $this->stringToCheckType($checkName);
        if ($checkType === null) {
            return null;
        }

        // Find matching problem
        foreach ($problems as $problem) {
            if ($problem->affects($checkType, $phpVersion)) {
                return $problem;
            }
        }

        return null;
    }

    /**
     * Check if validation should be skipped for an entity.
     *
     * @param string $entityType Entity type: 'functions', 'methods', 'classes'
     * @param string $entityId Fully qualified entity ID
     * @param string $checkName Name of the check class
     * @param string $phpVersion PHP version being tested
     * @return bool True if validation should be skipped
     */
    public function shouldSkipValidation(
        string $entityType,
        string $entityId,
        string $checkName,
        string $phpVersion
    ): bool {
        // All known problems skip validation by default
        return $this->hasProblem($entityType, $entityId, $checkName, $phpVersion);
    }

    /**
     * Get skip reason for an entity (for logging/reporting).
     *
     * @param string $entityType Entity type: 'functions', 'methods', 'classes'
     * @param string $entityId Fully qualified entity ID
     * @param string $checkName Name of the check class
     * @param string $phpVersion PHP version being tested
     * @return string|null Skip reason or null if validation should not be skipped
     */
    public function getSkipReason(
        string $entityType,
        string $entityId,
        string $checkName,
        string $phpVersion
    ): ?string {
        $problem = $this->getProblemDefinition($entityType, $entityId, $checkName, $phpVersion);

        return $problem?->reason;
    }

    /**
     * Convert check name string to CheckType enum.
     *
     * @param string $checkName Name of check class (e.g., 'ParameterNamesCheck')
     * @return CheckType|null CheckType enum or null if not found
     */
    private function stringToCheckType(string $checkName): ?CheckType
    {
        return match($checkName) {
            'ParameterNamesCheck' => CheckType::PARAMETER_NAMES,
            'ParameterTypesCheck' => CheckType::PARAMETER_TYPES,
            'ReturnTypesCheck' => CheckType::RETURN_TYPES,
            'FunctionExistsCheck' => CheckType::FUNCTION_EXISTS,
            'MethodExistsCheck' => CheckType::METHOD_EXISTS,
            'ClassExistsCheck' => CheckType::CLASS_EXISTS,
            'ClassParentClassCheck' => CheckType::CLASS_PARENT,
            'ClassInterfacesCheck' => CheckType::CLASS_INTERFACES,
            'ClassMethodsExistCheck' => CheckType::CLASS_METHODS_EXIST,
            'ClassFinalMethodsCheck' => CheckType::CLASS_FINAL_METHODS,
            'ClassStaticMethodsCheck' => CheckType::CLASS_STATIC_METHODS,
            'ClassPropertiesExistCheck' => CheckType::CLASS_PROPERTIES_EXIST,
            'ClassMethodsVisibilityCheck'    => CheckType::CLASS_METHODS_VISIBILITY,
            'ClassStaticPropertiesCheck'    => CheckType::CLASS_STATIC_PROPERTIES,
            'ClassPropertiesVisibilityCheck' => CheckType::CLASS_PROPERTIES_VISIBILITY,
            'ClassPropertiesTypeCheck'            => CheckType::CLASS_PROPERTIES_TYPE,
            'ParametersCountCheck'               => CheckType::PARAMETERS_COUNT,
            'DeprecationCheck'                   => CheckType::DEPRECATION,
            'OptionalParametersCheck'            => CheckType::OPTIONAL_PARAMETERS,
            'TentativeReturnTypeCheck'            => CheckType::TENTATIVE_RETURN_TYPE,
            'InterfaceParentInterfacesCheck'      => CheckType::INTERFACE_PARENT_INTERFACES,
            'EnumInterfacesCheck'                 => CheckType::ENUM_INTERFACES,
            'EnumCasesCheck'                      => CheckType::ENUM_CASES,
            'ClassFinalCheck'                     => CheckType::CLASS_FINAL,
            'EnumFinalCheck'                      => CheckType::ENUM_FINAL,
            'ClassConstantsCheck'                 => CheckType::CLASS_CONSTANTS,
            'InterfaceConstantsCheck'             => CheckType::INTERFACE_CONSTANTS,
            'EnumConstantsCheck'                  => CheckType::ENUM_CONSTANTS,
            'ClassConstantsVisibilityCheck'       => CheckType::CLASS_CONSTANTS_VISIBILITY,
            'EnumConstantsVisibilityCheck'        => CheckType::ENUM_CONSTANTS_VISIBILITY,
            'InterfaceConstantsVisibilityCheck'   => CheckType::INTERFACE_CONSTANTS_VISIBILITY,
            'ClassConstantsValueCheck'            => CheckType::CLASS_CONSTANTS_VALUE,
            'EnumConstantsValueCheck'             => CheckType::ENUM_CONSTANTS_VALUE,
            'InterfaceConstantsValueCheck'        => CheckType::INTERFACE_CONSTANTS_VALUE,
            'ClassPropertyReadonlyCheck'          => CheckType::CLASS_PROPERTIES_READONLY,
            'ConstantExistsCheck'                 => CheckType::CONSTANT_EXISTS,
            'ConstantValueCheck'                  => CheckType::CONSTANT_VALUE,
            'ParameterDefaultValueCheck'          => CheckType::PARAMETER_DEFAULT_VALUE,
            'PhpDocConformsSignatureCheck'        => CheckType::PHPDOC_CONFORMS_SIGNATURE,
            'ReturnTypeForbiddenCheck'             => CheckType::RETURN_TYPE_FORBIDDEN,
            'NullableTypeForbiddenCheck'           => CheckType::NULLABLE_TYPE_FORBIDDEN,
            'UnionTypeForbiddenCheck'              => CheckType::UNION_TYPE_FORBIDDEN,
            'ScalarTypeForbiddenCheck'             => CheckType::SCALAR_TYPE_FORBIDDEN,
            'PhpDocTagsCheck'                      => CheckType::PHPDOC_TAGS,
            'PhpDocVersionFormatCheck'             => CheckType::PHPDOC_VERSION_FORMAT,
            'PhpDocLinksCheck'                     => CheckType::PHPDOC_LINKS,
            'ReflectionMethodSpecialTypeHintsCheck' => CheckType::REFLECTION_SPECIAL_TYPE_HINTS,
            default => null,
        };
    }

    /**
     * Reset the singleton instance (useful for testing).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Get all registered problems (for debugging/reporting).
     *
     * @return ProblemDefinition[] All problems from provider
     */
    public function getAllProblems(): array
    {
        return $this->provider->getProblems();
    }

    /**
     * Get problems index (for debugging/reporting).
     *
     * @return array<string, array<string, ProblemDefinition[]>>
     */
    public function getProblemsIndex(): array
    {
        return $this->problemsIndex;
    }
}
