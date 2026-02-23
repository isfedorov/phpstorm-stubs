<?php

namespace StubTests\Sources\Validator\KnownProblems;

use StubTests\Sources\Runner\PhpVersionRange;

/**
 * Default implementation of KnownProblemsProvider.
 *
 * Defines all known validation problems for PHP stubs.
 * Problems are defined as type-safe PHP objects with compile-time validation.
 */
class DefaultKnownProblemsProvider implements KnownProblemsProvider
{
    /** @var ProblemDefinition[]|null Cached problems */
    private ?array $problems = null;

    /**
     * @inheritDoc
     */
    public function getProblems(): array
    {
        if ($this->problems !== null) {
            return $this->problems;
        }

        $this->problems = [
            // DBA extension - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\dba_fetch',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'dba_fetch has 2 overloaded signatures: dba_fetch($key, $handle) (2 params) and dba_fetch($key, $skip, $dba) (3 params, deprecated in 8.3). Reflection only returns one signature.'
            ),
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\dba_open',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'dba_open has 2 overloaded signatures with different parameter counts'
            ),
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\dba_popen',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'dba_popen has 2 overloaded signatures with different parameter counts'
            ),

            // String functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\strtr',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'strtr has 2 overloaded signatures: strtr($string, $from, $to) (3 params) and strtr($str, $replace_pairs) (2 params with array). Reflection returns only one.'
            ),

            // Session functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\session_set_save_handler',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'session_set_save_handler has 2 overloaded signatures: one with 9 callable parameters, one with SessionHandlerInterface object (2 params)'
            ),
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\session_set_cookie_params',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'session_set_cookie_params has 2 overloaded signatures with different parameter structures'
            ),

            // Cookie functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\setcookie',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'setcookie has 2 overloaded signatures: multiple scalar params vs array options param'
            ),
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\setrawcookie',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'setrawcookie has 2 overloaded signatures: multiple scalar params vs array options param'
            ),

            // GD functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\imagefilledpolygon',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'imagefilledpolygon has 2 overloaded signatures with different parameter structures'
            ),

            // Stream functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\stream_context_set_option',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'stream_context_set_option has 2 overloaded signatures: array param vs individual scalar params'
            ),

            // Multibyte string functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\mb_parse_str',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'mb_parse_str has 2 overloaded signatures with different parameter structures'
            ),

            // CUBRID database functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\cubrid_execute',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'cubrid_execute has 2 overloaded signatures with different parameter structures'
            ),

            // Standard functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\crypt',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange('5.6', '8.4'),
                reason: 'crypt has 2 overloaded signatures with different parameter structures'
            ),
        ];

        return $this->problems;
    }

    /**
     * @inheritDoc
     */
    public function getProblemsForEntity(EntityType $entityType, string $entityId): array
    {
        $allProblems = $this->getProblems();

        return array_filter(
            $allProblems,
            fn(ProblemDefinition $problem) =>
                $problem->entityType === $entityType
                && $problem->entityId === $entityId
        );
    }
}
