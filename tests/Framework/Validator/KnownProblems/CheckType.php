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
}
