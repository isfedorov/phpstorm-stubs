<?php

namespace StubTests\Sources\Validator\KnownProblems;

use StubTests\Sources\Runner\PhpVersionRange;
use StubTests\Sources\Runner\PhpVersions;

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
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'dba_fetch has 2 overloaded signatures: dba_fetch($key, $handle) (2 params) and dba_fetch($key, $skip, $dba) (3 params, deprecated in 8.3). Reflection only returns one signature.'
            ),
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\dba_open',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'dba_open has 2 overloaded signatures with different parameter counts'
            ),
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\dba_popen',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'dba_popen has 2 overloaded signatures with different parameter counts'
            ),

            // String functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\strtr',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'strtr has 2 overloaded signatures: strtr($string, $from, $to) (3 params) and strtr($str, $replace_pairs) (2 params with array). Reflection returns only one.'
            ),

            // Session functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\session_set_save_handler',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'session_set_save_handler has 2 overloaded signatures: one with 9 callable parameters, one with SessionHandlerInterface object (2 params)'
            ),
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\session_set_cookie_params',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'session_set_cookie_params has 2 overloaded signatures with different parameter structures'
            ),

            // Cookie functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\setcookie',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'setcookie has 2 overloaded signatures: multiple scalar params vs array options param'
            ),
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\setrawcookie',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'setrawcookie has 2 overloaded signatures: multiple scalar params vs array options param'
            ),

            // GD functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\imagefilledpolygon',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'imagefilledpolygon has 2 overloaded signatures with different parameter structures'
            ),

            // Stream functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\stream_context_set_option',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'stream_context_set_option has 2 overloaded signatures: array param vs individual scalar params'
            ),

            // Multibyte string functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\mb_parse_str',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'mb_parse_str has 2 overloaded signatures with different parameter structures'
            ),

            // CUBRID database functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\cubrid_execute',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'cubrid_execute has 2 overloaded signatures with different parameter structures'
            ),

            // Standard functions - overloaded signatures
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\crypt',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETER_NAMES, CheckType::PARAMETER_TYPES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'crypt has 2 overloaded signatures with different parameter structures'
            ),

            // SimpleXMLElement - ArrayAccess implemented at C level, not visible to reflection
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\SimpleXMLElement',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'SimpleXMLElement implements ArrayAccess at the C level without declaring it via `implements`. PHP reflection never reports ArrayAccess, but the stub adds it explicitly so PhpStorm can perform array-offset type inference on SimpleXMLElement instances.'
            ),

            // SplFileInfo - Stringable added in PHP 8.0; stubs already declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\SplFileInfo',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'SplFileInfo gained Stringable in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare Stringable for all versions. Reflection for PHP 5.6–7.4 does not report Stringable.'
            ),

            // SplObjectStorage - SeekableIterator added in PHP 8.4; stubs already declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\SplObjectStorage',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_8_3),
                reason: 'SplObjectStorage gained SeekableIterator in PHP 8.4. PhpStorm cannot express per-version interface declarations, so stubs declare SeekableIterator for all versions. Reflection for PHP 5.6–8.3 does not report SeekableIterator.'
            ),

            // Exception - Throwable did not exist in PHP 5.6; stubs declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\Exception',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_5_6),
                reason: 'Throwable was introduced in PHP 7.0. Stubs declare Exception implements Throwable for all versions, but PHP 5.6 reflection does not report it.'
            ),

            // GMP - Serializable implemented internally, never visible to reflection
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\GMP',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'GMP implements Serializable at the C level. PHP reflection never reports Serializable for GMP across any version, but stubs declare it explicitly for serialization support in PhpStorm.'
            ),

            // ReflectionType - Stringable added in PHP 8.0; stubs declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\ReflectionType',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::PHP_7_0, PhpVersions::PHP_7_4),
                reason: 'ReflectionType gained Stringable in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare Stringable for all versions. Reflection for PHP 7.0–7.4 does not report Stringable.'
            ),

            // ReflectionAttribute - Reflector added in PHP 8.1; stubs declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\ReflectionAttribute',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::PHP_8_0, PhpVersions::PHP_8_0),
                reason: 'ReflectionAttribute gained Reflector in PHP 8.1. PhpStorm cannot express per-version interface declarations, so stubs declare Reflector for all versions. Reflection for PHP 8.0 does not report Reflector.'
            ),

            // DatePeriod - IteratorAggregate added in PHP 8.0; stubs declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\DatePeriod',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DatePeriod gained IteratorAggregate in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare IteratorAggregate for all versions. Reflection for PHP 5.6–7.4 does not report IteratorAggregate.'
            ),

            // IntlBreakIterator - IteratorAggregate added in PHP 8.0; stubs declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\IntlBreakIterator',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'IntlBreakIterator gained IteratorAggregate in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare IteratorAggregate for all versions. Reflection for PHP 5.6–7.4 does not report IteratorAggregate.'
            ),

            // PDOStatement - IteratorAggregate added in PHP 8.0; stubs declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\PDOStatement',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'PDOStatement gained IteratorAggregate in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare IteratorAggregate for all versions. Reflection for PHP 5.6–7.4 does not report IteratorAggregate.'
            ),

            // mysqli_result - IteratorAggregate added in PHP 8.0; stubs declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\mysqli_result',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'mysqli_result gained IteratorAggregate in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare IteratorAggregate for all versions. Reflection for PHP 5.6–7.4 does not report IteratorAggregate.'
            ),

            // CachingIterator - Stringable added in PHP 8.0; stubs declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\CachingIterator',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'CachingIterator gained Stringable in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare Stringable for all versions. Reflection for PHP 5.6–7.4 does not report Stringable.'
            ),

            // SimpleXMLIterator - Stringable added in PHP 8.0; stubs declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\SimpleXMLIterator',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'SimpleXMLIterator gained Stringable in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare Stringable for all versions. Reflection for PHP 5.6–7.4 does not report Stringable.'
            ),

            // DOMCharacterData - DOMChildNode added in PHP 8.0; stubs declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\DOMCharacterData',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DOMCharacterData gained DOMChildNode in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare DOMChildNode for all versions. Reflection for PHP 5.6–7.4 does not report DOMChildNode.'
            ),

            // DOMDocumentFragment - DOMParentNode added in PHP 8.0; stubs declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\DOMDocumentFragment',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DOMDocumentFragment gained DOMParentNode in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare DOMParentNode for all versions. Reflection for PHP 5.6–7.4 does not report DOMParentNode.'
            ),

            // DOMDocument - DOMParentNode added in PHP 8.0; stubs declare it for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\DOMDocument',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DOMDocument gained DOMParentNode in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare DOMParentNode for all versions. Reflection for PHP 5.6–7.4 does not report DOMParentNode.'
            ),

            // DOMElement - DOMChildNode and DOMParentNode added in PHP 8.0; stubs declare them for all versions
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\DOMElement',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DOMElement gained DOMChildNode and DOMParentNode in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare both for all versions. Reflection for PHP 5.6–7.4 does not report them.'
            ),

            // DOMNamedNodeMap - Countable added in PHP 7.2, IteratorAggregate added in PHP 8.0
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\DOMNamedNodeMap',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DOMNamedNodeMap gained Countable in PHP 7.2 and IteratorAggregate in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare both for all versions. Reflection for PHP 5.6–7.1 does not report Countable; reflection for PHP 5.6–7.4 does not report IteratorAggregate.'
            ),

            // DOMNodeList - Countable added in PHP 7.2, IteratorAggregate added in PHP 8.0
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\DOMNodeList',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DOMNodeList gained Countable in PHP 7.2 and IteratorAggregate in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare both for all versions. Reflection for PHP 5.6–7.1 does not report Countable; reflection for PHP 5.6–7.4 does not report IteratorAggregate.'
            ),

            // ResourceBundle - Countable added in PHP 7.4, IteratorAggregate added in PHP 8.0
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\ResourceBundle',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'ResourceBundle gained Countable in PHP 7.4 and IteratorAggregate in PHP 8.0. PhpStorm cannot express per-version interface declarations, so stubs declare both for all versions. Reflection for PHP 5.6–7.3 does not report Countable; reflection for PHP 5.6–7.4 does not report IteratorAggregate.'
            ),

            // SimpleXMLElement::__construct - final at C level in PHP 5.6–7.4; changed in PHP 8.0
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\SimpleXMLElement::__construct',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_FINAL_METHODS],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'SimpleXMLElement::__construct was marked final at the C level in PHP 5.6–7.4. This was changed in PHP 8.0. The stub declares the constructor without final (matching PHP 8.0+ behaviour), but reflection for PHP 5.6–7.4 reports isFinal=true.'
            ),

            // SimpleXMLIterator::__construct - inherits SimpleXMLElement::__construct which was final at C level in PHP 5.6–7.4
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\SimpleXMLIterator::__construct',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_FINAL_METHODS],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'SimpleXMLIterator extends SimpleXMLElement and inherits __construct. Since SimpleXMLElement::__construct was marked final at the C level in PHP 5.6–7.4, reflection reports isFinal=true for the inherited constructor on SimpleXMLIterator as well. This was changed in PHP 8.0. The stub declares the constructor without final (matching PHP 8.0+ behaviour).'
            ),

            // XMLReader::open - became truly static in PHP 8.0; stubs declare it static for all versions
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\XMLReader::open',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_STATIC_METHODS],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'XMLReader::open was a non-static instance method in PHP 5.6–7.4 (though callable statically with a deprecation notice). It was made officially static in PHP 8.0. The stub declares it static to match the PHP 8.0+ signature; reflection for PHP 5.6–7.4 reports isStatic=false.'
            ),

            // XMLReader::XML - became truly static in PHP 8.0; stubs declare it static for all versions
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\XMLReader::XML',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_STATIC_METHODS],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'XMLReader::XML was a non-static instance method in PHP 5.6–7.4 (though callable statically with a deprecation notice). It was made officially static in PHP 8.0. The stub declares it static to match the PHP 8.0+ signature; reflection for PHP 5.6–7.4 reports isStatic=false.'
            ),

            // SplFixedArray - interfaces changed across PHP versions; stubs declare the union
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\SplFixedArray',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_INTERFACES],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'SplFixedArray interface list changed across PHP versions: Iterator (5.6–7.4) was replaced by IteratorAggregate (8.0+), and JsonSerializable was added in 8.1. PhpStorm cannot express per-version interface declarations, so stubs declare the union of all interfaces. Each individual PHP version\'s reflection only reports the subset current for that version.'
            ),

            // SoapClient - internal C-level implementation properties not declared in stubs
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\SoapClient',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_PROPERTIES_EXIST],
                versionRange: new PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST),
                reason: 'SoapClient exposes numerous private C-level implementation properties (e.g. $sdl, $typemap, $_encoding, $httpsocket) that became visible via reflection in PHP 8.1 after an internal refactoring. These are undocumented implementation details not intended for user access and are not declared in stubs.'
            ),

            // SoapServer - internal C-level implementation properties not declared in stubs
            new ProblemDefinition(
                entityType: EntityType::CLASS_TYPE,
                entityId: '\\SoapServer',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::CLASS_PROPERTIES_EXIST],
                versionRange: new PhpVersionRange(PhpVersions::PHP_8_1, PhpVersions::LATEST),
                reason: 'SoapServer exposes internal C-level implementation properties ($service, $__soap_fault) that became visible via reflection in PHP 8.1 after an internal refactoring. These are undocumented implementation details not intended for user access and are not declared in stubs.'
            ),

            // ── ClassMethodsParametersCountCheck known problems ───────────────────────

            // Closure::__invoke - reflection reports the concrete closure signature (0 params for the generic stub)
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\Closure::__invoke',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::PHP_7_2, PhpVersions::LATEST),
                reason: 'Closure::__invoke reflects the actual closure signature. PHP reflection returns 0 parameters for a generic Closure, but the stub declares 1 placeholder parameter for IDE support.'
            ),

            // DateTime::__set_state - reflection reports 0 params in PHP 5.6–7.2; PHP 7.3+ fixed
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DateTime::__set_state',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_2),
                reason: 'DateTime::__set_state is documented with 1 parameter ($array), but reflection in PHP 5.6–7.2 reports 0 parameters. PHP 7.3 corrected the reflection metadata.'
            ),

            // DateTimeImmutable::__set_state - same issue
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DateTimeImmutable::__set_state',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_2),
                reason: 'DateTimeImmutable::__set_state is documented with 1 parameter ($array), but reflection in PHP 5.6–7.2 reports 0 parameters. PHP 7.3 corrected the reflection metadata.'
            ),

            // DateTimeZone::__set_state - same issue
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DateTimeZone::__set_state',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_2),
                reason: 'DateTimeZone::__set_state is documented with 1 parameter ($array), but reflection in PHP 5.6–7.2 reports 0 parameters. PHP 7.3 corrected the reflection metadata.'
            ),

            // DateInterval::__set_state - same issue
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DateInterval::__set_state',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_2),
                reason: 'DateInterval::__set_state is documented with 1 parameter ($array), but reflection in PHP 5.6–7.2 reports 0 parameters. PHP 7.3 corrected the reflection metadata.'
            ),

            // DatePeriod::__construct - overloaded signature (DatePeriod accepts multiple constructor forms)
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DatePeriod::__construct',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DatePeriod::__construct has multiple overloaded forms. Stubs document all parameters across all overloads (4 params), but reflection for PHP 5.6–7.4 returns only 3 parameters.'
            ),

            // DOMImplementation::hasFeature - deprecated no-op; reflection reports 0 params in older PHP
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DOMImplementation::hasFeature',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DOMImplementation::hasFeature is a deprecated no-op. Reflection in PHP 5.6–7.4 reports 0 parameters, but the stub correctly declares 2 parameters ($feature, $version) per the DOM specification.'
            ),

            // DOMDocument::save - optional $options parameter not reported by reflection in PHP 5.6–7.4
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DOMDocument::save',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DOMDocument::save has an optional $options parameter that was not exposed by reflection in PHP 5.6–7.4. The stub declares both $filename and $options (2 params), but reflection reports only 1.'
            ),

            // DOMDocument::saveHTML - optional $node parameter not reported by reflection in PHP 5.6–7.4
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DOMDocument::saveHTML',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DOMDocument::saveHTML has an optional $node parameter that was not exposed by reflection in PHP 5.6–7.4. The stub declares 1 parameter, but reflection reports 0.'
            ),

            // DOMDocument::schemaValidate - optional $flags parameter not in reflection for PHP 5.6–7.4
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DOMDocument::schemaValidate',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DOMDocument::schemaValidate has an optional $flags parameter not reported by reflection in PHP 5.6–7.4. Stubs declare 2 params, reflection reports 1.'
            ),

            // DOMDocument::schemaValidateSource - optional $flags parameter not in reflection for PHP 5.6–7.4
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DOMDocument::schemaValidateSource',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DOMDocument::schemaValidateSource has an optional $flags parameter not reported by reflection in PHP 5.6–7.4. Stubs declare 2 params, reflection reports 1.'
            ),

            // DOMXPath::registerPhpFunctions - optional $restrict parameter not in reflection for PHP 5.6–7.4
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DOMXPath::registerPhpFunctions',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'DOMXPath::registerPhpFunctions has an optional $restrict parameter not reported by reflection in PHP 5.6–7.4. Stubs declare 1 parameter, reflection reports 0.'
            ),

            // ArrayObject::__construct - reflection in PHP 5.6 reports only 1 param; stub has 3
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\ArrayObject::__construct',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_5_6),
                reason: 'ArrayObject::__construct reflection in PHP 5.6 reports only 1 parameter, but the stub declares 3 ($array, $flags, $iteratorClass). PHP 7.0+ reflection correctly reports all 3.'
            ),

            // SplHeap::compare - abstract method; reflection in PHP 5.6–7.4 reports 0 params
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\SplHeap::compare',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'SplHeap::compare is an abstract method. Reflection in PHP 5.6–7.4 reports 0 parameters, but the stub declares 2 ($value1, $value2) matching the intended override contract.'
            ),

            // PDO::query - overloaded signature; reflection reports fewer params in PHP 5.6–7.4
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\PDO::query',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'PDO::query has multiple overloaded forms with different parameter counts. Reflection in PHP 5.6–7.4 reports fewer parameters than the stub, which documents all forms.'
            ),

            // XMLWriter::writeDtdEntity - reflection reports 2 params in PHP 5.6–7.4; stub has 6
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\XMLWriter::writeDtdEntity',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'XMLWriter::writeDtdEntity reflection in PHP 5.6–7.4 reports only 2 parameters, but the stub declares 6 ($name, $content, $pe, $pubid, $sysid, $ndataid) per the XML spec. PHP 8.0+ reflection correctly reports all 6.'
            ),

            // mysqli_stmt::__construct - reflection reports 0 params in PHP 5.6–7.4
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\mysqli_stmt::__construct',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'mysqli_stmt::__construct reflection in PHP 5.6–7.4 reports 0 parameters, but the stub declares 2 ($mysql, $query). PHP 8.0+ reflection correctly reports them.'
            ),

            // mysqli_stmt::bind_param - variadic; reflection reports 2 params, stub has 3 (types + vars + variadic)
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\mysqli_stmt::bind_param',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'mysqli_stmt::bind_param is variadic. Reflection reports 2 parameters ($types + variadic &$var), but the stub declares 3 ($types, $var1, &...$vars) to document the required first variable explicitly for IDE support.'
            ),

            // mysqli_stmt::bind_result - variadic; reflection reports 1 param, stub has 2
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\mysqli_stmt::bind_result',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'mysqli_stmt::bind_result is variadic. Reflection reports 1 parameter (variadic &$var), but the stub declares 2 ($var1, &...$vars) to document the required first variable explicitly for IDE support.'
            ),

            // SoapFault::__construct - reflection reports fewer params in PHP 5.6–7.4
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\SoapFault::__construct',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'SoapFault::__construct reflection in PHP 5.6–7.4 reports fewer parameters than the stub. The stub documents the full constructor signature including optional parameters not exposed by older reflection.'
            ),

            // ── FunctionParametersCountCheck known problems ───────────────────────

            // dba_fetch - overloaded signature; reflection returns 3-param form, stub selects 2-param form
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\dba_fetch',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'dba_fetch has 2 overloaded signatures: dba_fetch($key, $handle) (2 params) and dba_fetch($key, $skip, $dba) (3 params, deprecated in 8.3). Reflection returns the 3-param form, but the stub selects the 2-param form.'
            ),

            // session_set_cookie_params - PHP 7.3+ reflection returns 5-param legacy form
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\session_set_cookie_params',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::PHP_7_3, PhpVersions::LATEST),
                reason: 'session_set_cookie_params has two overloaded forms: a 1-param array form (PHP 7.3+) and a 5-param scalar form. PHP 7.3+ reflection returns 5 parameters (legacy form), but the stub selects the 1-param array variant.'
            ),

            // session_set_save_handler - 9-param stub form has 2 extra params not present in PHP 5.6
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\session_set_save_handler',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::PARAMETERS_COUNT],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_5_6),
                reason: 'session_set_save_handler stub declares 9 callable parameters including validate_sid and update_timestamp added in PHP 7.0. Reflection in PHP 5.6 reports only 7 parameters.'
            ),

            // ── FunctionOptionalParametersCheck known problems ────────────────────────

            // strtr - overloaded signature; $to is optional in the 2-arg form strtr($str, $pairs)
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\strtr',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::OPTIONAL_PARAMETERS],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'strtr has 2 overloaded signatures. In the 2-arg form strtr($str, $pairs_map), $to is absent; reflection reports $to as optional. Stubs cannot mark $to optional in the 3-arg overload without incorrectly allowing strtr($str, $from).'
            ),

            // crypt - $salt was optional in PHP 5.6-7.4 (deprecated auto-generation)
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\crypt',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::OPTIONAL_PARAMETERS],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'In PHP 5.6-7.4, crypt() could be called without $salt (deprecated auto-generation). Reflection marks $salt as optional. The stub requires $salt to discourage the deprecated usage.'
            ),

            // dba_fetch - overloaded signature; reflection reports $handle/$dba as optional
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\dba_fetch',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::OPTIONAL_PARAMETERS],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'dba_fetch has 2 overloaded signatures. Reflection marks the handle/dba parameter as optional because the function can accept either 2 or 3 args. Stubs cannot express this without marking the handle optional in the 2-arg overload.'
            ),

            // session_set_save_handler - 9-param overload; reflection marks params 2-9 as optional
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\session_set_save_handler',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::OPTIONAL_PARAMETERS],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::LATEST),
                reason: 'session_set_save_handler has 2 overloaded signatures. Reflection reports most callable parameters as optional (since the 2-arg SessionHandlerInterface form omits them), but the 9-arg callable form requires them.'
            ),

            // imagefilledpolygon - PHP 8.0 changed parameter order; reflection marks $color as optional
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\imagefilledpolygon',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::OPTIONAL_PARAMETERS],
                versionRange: new PhpVersionRange(PhpVersions::PHP_8_0, PhpVersions::LATEST),
                reason: 'imagefilledpolygon has 2 overloaded signatures in PHP 8.0+ (3-arg and 4-arg forms). Reflection marks $color as optional because the function can be called with 3 args. Stubs use separate version-specific definitions that cannot mark $color optional without breaking the 4-arg overload.'
            ),

            // stream_context_set_option - overloaded signature; reflection marks $option_name/$value as optional
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\stream_context_set_option',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::OPTIONAL_PARAMETERS],
                versionRange: new PhpVersionRange(PhpVersions::PHP_7_4, PhpVersions::LATEST),
                reason: 'stream_context_set_option has 2 overloaded signatures: array-options form and individual scalar params form. Reflection marks $option_name and $value as optional since the function can be called with 2 args (context + options array). Stubs cannot express this without marking them optional in the 4-arg overload.'
            ),

            // implode - PHP 8.0+ $array is optional in the 1-arg BC form implode($array)
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\implode',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::OPTIONAL_PARAMETERS],
                versionRange: new PhpVersionRange(PhpVersions::PHP_8_0, PhpVersions::LATEST),
                reason: 'implode() accepts both implode($separator, $array) and the BC form implode($array). Reflection marks $array as optional. The stub uses array|string $separator to model both forms but cannot mark $array as truly optional without allowing a zero-argument call.'
            ),

            // join - alias of implode; same known problem
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\join',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::OPTIONAL_PARAMETERS],
                versionRange: new PhpVersionRange(PhpVersions::PHP_8_0, PhpVersions::LATEST),
                reason: 'join() is an alias of implode(). Same known problem: $array is optional in the 1-arg BC form, but the stub cannot express this without allowing a zero-argument call.'
            ),

            // hash_update_file - PHP 5.6-7.0 named the 3rd param "context" (same as 1st stub param); false-positive
            new ProblemDefinition(
                entityType: EntityType::FUNCTION,
                entityId: '\\hash_update_file',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::OPTIONAL_PARAMETERS],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_0),
                reason: 'In PHP 5.6-7.0, the 3rd parameter of hash_update_file() was named "context" in reflection, colliding with the 1st stub param name "context" (HashContext). The optional-parameters check incorrectly matches the required 1st stub param against the optional 3rd reflection param.'
            ),

            // ── ClassMethodsOptionalParametersCheck known problems ───────────────────

            // SoapFault::__construct - PHP 5.6-7.4 reflection marks $code as optional (C-extension quirk)
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\SoapFault::__construct',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::OPTIONAL_PARAMETERS],
                versionRange: new PhpVersionRange(PhpVersions::EARLIEST, PhpVersions::PHP_7_4),
                reason: 'SoapFault::__construct in PHP 5.6-7.4: reflection marks $code as optional due to C-extension implementation detail. The parameter is required in the public API.'
            ),

            // DOMNamedNodeMap::item - PHP 7.1-7.4 reflection marks $index as optional (C-extension keeps default = 0)
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DOMNamedNodeMap::item',
                type: ProblemType::INTERNAL_IMPLEMENTATION,
                affectedChecks: [CheckType::OPTIONAL_PARAMETERS],
                versionRange: new PhpVersionRange(PhpVersions::PHP_7_1, PhpVersions::PHP_7_4),
                reason: 'DOMNamedNodeMap::item in PHP 7.1-7.4: reflection marks $index as optional (default = 0 retained in C implementation). The stub intentionally requires $index for PHP 7.1+ to enforce correct usage.'
            ),

            // DatePeriod::__construct - PHP 8.0+ reflection marks $interval and $end as optional (overloaded constructor)
            new ProblemDefinition(
                entityType: EntityType::METHOD,
                entityId: '\\DatePeriod::__construct',
                type: ProblemType::OVERLOADED_SIGNATURE,
                affectedChecks: [CheckType::OPTIONAL_PARAMETERS],
                versionRange: new PhpVersionRange(PhpVersions::PHP_8_0, PhpVersions::LATEST),
                reason: 'DatePeriod::__construct has 3 overloaded forms: (start, interval, end, options), (start, interval, recurrences, options), and (isostr, options). Reflection marks $interval and $end as optional due to multi-arity overloading. Stubs express each overload separately.'
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
