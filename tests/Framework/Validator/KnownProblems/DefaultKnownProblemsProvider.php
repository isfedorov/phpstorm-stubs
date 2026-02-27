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
