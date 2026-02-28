<?php

namespace StubTests\Sources\Validator\KnownProblems;

/**
 * Enumeration of validator check types.
 *
 * Each case represents a specific validation check that can be affected
 * by known problems. The enum value is the actual class name of the check.
 */
enum CheckType: string
{
    /**
     * Validates that parameter names in stubs match reflection.
     * Relevant for PHP 8.0+ where named parameters were introduced.
     */
    case PARAMETER_NAMES = 'ParameterNamesCheck';

    /**
     * Validates that parameter types in stubs match reflection.
     * Checks type hints for all parameters.
     */
    case PARAMETER_TYPES = 'ParameterTypesCheck';

    /**
     * Validates that return types in stubs match reflection.
     * Checks return type declarations.
     */
    case RETURN_TYPES = 'ReturnTypesCheck';

    /**
     * Validates that functions exist in reflection.
     * Checks basic existence of entities.
     */
    case FUNCTION_EXISTS = 'FunctionExistsCheck';

    /**
     * Validates that methods exist in reflection.
     */
    case METHOD_EXISTS = 'MethodExistsCheck';

    /**
     * Validates that classes exist in reflection.
     */
    case CLASS_EXISTS = 'ClassExistsCheck';

    /**
     * Validates that parent class in stubs matches parent class in reflection.
     */
    case CLASS_PARENT = 'ClassParentClassCheck';

    /**
     * Validates that directly implemented interfaces in stubs match reflection.
     */
    case CLASS_INTERFACES = 'ClassInterfacesCheck';

    /**
     * Validates that all methods present in reflection also exist in stubs.
     */
    case CLASS_METHODS_EXIST = 'ClassMethodsExistCheck';

    /**
     * Validates that the `final` attribute on methods in stubs matches reflection.
     */
    case CLASS_FINAL_METHODS = 'ClassFinalMethodsCheck';

    /**
     * Validates that the `static` attribute on methods in stubs matches reflection.
     */
    case CLASS_STATIC_METHODS = 'ClassStaticMethodsCheck';

    /**
     * Validates that all properties present in reflection also exist in stubs.
     */
    case CLASS_PROPERTIES_EXIST = 'ClassPropertiesExistCheck';

    /**
     * Validates that the visibility (public/protected/private) of methods in stubs matches reflection.
     */
    case CLASS_METHODS_VISIBILITY = 'ClassMethodsVisibilityCheck';

    /**
     * Validates that the `static` attribute on properties in stubs matches reflection.
     */
    case CLASS_STATIC_PROPERTIES = 'ClassStaticPropertiesCheck';

    /**
     * Validates that the visibility (public/protected/private) of properties in stubs matches reflection.
     */
    case CLASS_PROPERTIES_VISIBILITY = 'ClassPropertiesVisibilityCheck';

    /**
     * Validates that the declared type of properties in stubs matches reflection.
     * Supports LanguageLevelTypeAware version-specific types.
     */
    case CLASS_PROPERTIES_TYPE = 'ClassPropertiesTypeCheck';

    /**
     * Validates that the number of parameters in stub methods/functions matches reflection.
     * Accounts for PhpStormStubsElementAvailable version-filtered parameters.
     * Used by both ClassMethodsParametersCountCheck and FunctionParametersCountCheck.
     */
    case PARAMETERS_COUNT = 'ParametersCountCheck';

    /**
     * Validates that functions/methods deprecated in reflection are also marked deprecated in stubs.
     * Used by both FunctionDeprecationCheck and MethodDeprecationCheck.
     */
    case DEPRECATION = 'DeprecationCheck';
}
