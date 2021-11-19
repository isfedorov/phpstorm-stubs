<?php
declare(strict_types=1);

namespace StubTests;

use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use phpDocumentor\Reflection\DocBlock\Tags\Link;
use phpDocumentor\Reflection\DocBlock\Tags\Reference\Url;
use phpDocumentor\Reflection\DocBlock\Tags\See;
use phpDocumentor\Reflection\DocBlock\Tags\Since;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Null_;
use PhpParser\Comment\Doc;
use PHPUnit\Framework\Exception;
use StubTests\Model\BasePHPClass;
use StubTests\Model\BasePHPElement;
use StubTests\Model\PHPConst;
use StubTests\Model\PHPDocElement;
use StubTests\Model\PHPFunction;
use StubTests\Model\PHPMethod;
use StubTests\Model\StubProblemType;
use StubTests\Model\Tags\RemovedTag;
use StubTests\Parsers\DocFactoryProvider;
use StubTests\Parsers\ParserUtils;
use function trim;

class StubsPhpDocTest extends BaseStubsTest
{
    /**
     * @dataProvider \StubTests\TestData\Providers\Stubs\StubConstantsProvider::classConstantProvider
     * @throws Exception
     */
    public static function testClassConstantsPHPDocs(BasePHPClass $class, PHPConst $constant): void
    {
        self::assertNull($constant->parseError, $constant->parseError ?: '');
        self::checkPHPDocCorrectness($constant, "constant $class->sourceFilePath/$class->name::$constant->name");
    }

    /**
     * @dataProvider \StubTests\TestData\Providers\Stubs\StubConstantsProvider::globalConstantProvider
     * @throws Exception
     */
    public static function testConstantsPHPDocs(PHPConst $constant): void
    {
        self::assertNull($constant->parseError, $constant->parseError ?: '');
        self::checkPHPDocCorrectness($constant, "constant $constant->name");
    }

    /**
     * @dataProvider \StubTests\TestData\Providers\Stubs\StubsTestDataProviders::allFunctionsProvider
     * @throws Exception
     */
    public static function testFunctionPHPDocs(PHPFunction $function): void
    {
        self::assertNull($function->parseError, $function->parseError ?: '');
        self::compareWithOfficialDocs($function);
        self::checkPHPDocCorrectness($function, "function $function->name");
    }

    /**
     * @dataProvider \StubTests\TestData\Providers\Stubs\StubsTestDataProviders::allClassesProvider
     * @throws Exception
     */
    public static function testClassesPHPDocs(BasePHPClass $class): void
    {
        self::assertNull($class->parseError, $class->parseError ?: '');
        self::checkPHPDocCorrectness($class, "class $class->name");
    }

    /**
     * @dataProvider \StubTests\TestData\Providers\Stubs\StubMethodsProvider::allMethodsProvider
     * @throws Exception
     */
    public static function testMethodsPHPDocs(PHPMethod $method): void
    {
        if ($method->name === '__construct') {
            self::assertEmpty($method->returnTypesFromPhpDoc, '@return tag for __construct should be omitted');
        }
        self::assertNull($method->parseError, $method->parseError ?: '');
        self::compareWithOfficialDocs($method);
        self::checkPHPDocCorrectness($method, "method $method->name");
    }

    //TODO IF: Add test to check that phpdocs contain only resource, object etc typehints or if contains type like Resource then Resource should be declared in stubs

    private static function checkDeprecatedRemovedSinceVersionsMajor(BasePHPElement $element, string $elementName): void
    {
        /** @var PHPDocElement $element */
        foreach ($element->sinceTags as $sinceTag) {
            if ($sinceTag instanceof Since) {
                $version = $sinceTag->getVersion();
                if ($version !== null) {
                    self::assertTrue(ParserUtils::tagDoesNotHaveZeroPatchVersion($sinceTag), "$elementName has 
                    'since' version $version.'Since' version for PHP Core functionality for style consistency 
                    should have X.X format for the case when patch version is '0'.");
                }
            }
        }
        foreach ($element->deprecatedTags as $deprecatedTag) {
            if ($deprecatedTag instanceof Deprecated) {
                $version = $deprecatedTag->getVersion();
                if ($version !== null) {
                    self::assertTrue(ParserUtils::tagDoesNotHaveZeroPatchVersion($deprecatedTag), "$elementName has 
                    'deprecated' version $version.'Deprecated' version for PHP Core functionality for style consistency 
                    should have X.X format for the case when patch version is '0'.");
                }
            }
        }
        foreach ($element->removedTags as $removedTag) {
            if ($removedTag instanceof RemovedTag) {
                $version = $removedTag->getVersion();
                if ($version !== null) {
                    self::assertTrue(ParserUtils::tagDoesNotHaveZeroPatchVersion($removedTag), "$elementName has 
                    'removed' version $version.'Removed' version for PHP Core functionality for style consistency 
                    should have X.X format for the case when patch version is '0'.");
                }
            }
        }
    }

    private static function checkHtmlTags(BasePHPElement $element, string $elementName): void
    {
        /** @var PHPDocElement $element */
        $phpdoc = trim($element->phpdoc);

        $phpdoc = preg_replace(
            [
                '#<br\s*/>#',
                '#<br>#i',
                '#->#',
                '#=>#',
                '#"->"#',
                '# >= #',
                '#\(>=#',
                '#\'>\'#',
                '# > #',
                '#\?>#',
                '#`<.*>`#U',
                '#`.*<.*>`#U',
                '#<pre>.*</pre>#sU',
                '#<code>.*</code>#sU',
                '#@author.*<.*>#U',
                '#[\s,\|]array<[a-z,\s]+>#sU',
                '#\s[A-Za-z]+<[A-Za-z,\s]+>[$\s]#sU',
                '#<\w+\s*,\s*\w+>#'
            ],
            '',
            $phpdoc
        );

        $countTags = substr_count($phpdoc, '>');
        self::assertSame(
            0,
            $countTags % 2,
            "In $elementName phpdoc has a html error and the phpdoc maybe not displayed correctly in PhpStorm: " . print_r($phpdoc, true)
        );
    }

    private static function checkLinks(BasePHPElement $element, string $elementName): void
    {
        /** @var PHPDocElement $element */
        foreach ($element->links as $link) {
            if ($link instanceof Link) {
                self::assertStringStartsWith(
                    'https',
                    $link->getLink(),
                    "In $elementName @link doesn't start with https"
                );
                if (getenv('CHECK_LINKS') === 'true') {
                    if ($element->stubBelongsToCore) {
                        $request = curl_init($link->getLink());
                        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
                        curl_exec($request);
                        $response = curl_getinfo($request, CURLINFO_RESPONSE_CODE);
                        curl_close($request);
                        self::assertTrue($response < 400);
                    }
                }
            }
        }
        foreach ($element->see as $see) {
            if ($see instanceof See && $see->getReference() instanceof Url) {
                $uri = (string)$see->getReference();
                self::assertStringStartsWith('https', $uri, "In $elementName @see doesn't start with https");
            }
        }
    }

    /**
     * @throws Exception
     */
    private static function checkContainsOnlyValidTags(BasePHPElement $element, string $elementName): void
    {
        $VALID_TAGS = [
            'author',
            'copyright',
            'deprecated',
            'example', //temporary addition due to the number of existing cases
            'inheritdoc',
            'inheritDoc',
            'internal',
            'link',
            'meta',
            'method',
            'mixin',
            'package',
            'param',
            'property',
            'property-read',
            'removed',
            'return',
            'see',
            'since',
            'throws',
            'template',
            'template-implements', // https://github.com/JetBrains/phpstorm-stubs/pull/1212#issuecomment-907263735
            'template-extends',
            'uses',
            'var',
            'version',
        ];
        /** @var PHPDocElement $element */
        foreach ($element->tagNames as $tagName) {
            self::assertContains($tagName, $VALID_TAGS, "Element $elementName has invalid tag: @$tagName");
        }
    }

    /**
     * @throws Exception
     */
    private static function checkPHPDocCorrectness(BasePHPElement $element, string $elementName): void
    {
        self::checkLinks($element, $elementName);
        self::checkHtmlTags($element, $elementName);
        if ($element->stubBelongsToCore) {
            self::checkDeprecatedRemovedSinceVersionsMajor($element, $elementName);
        }
        self::checkContainsOnlyValidTags($element, $elementName);
    }
    private static function compareWithOfficialDocs(PHPFunction|PHPMethod $function)
    {
        $doc = $function->doc;
        $function_name = $function instanceof PHPMethod ? $function->parentName . "." . $function->name : $function->name;
        if ($doc !== null) {
            self::validateParameters($function, $doc, $function_name);
        }
        //$this->validateReturnType($function_name, $function);
        //$docBlockSummary = $doc != null ? DocFactoryProvider::getDocFactory()->create($doc->getText())->getSummary() : "";
        //$this->checkSummary($functionsData, $docBlockSummary, $function_name);
    }

    private static function normalizeSummary(string $summary)
    {
        $summary = preg_replace('/\s+/', ' ', $summary);
        $summary = preg_replace('/\\n/', '', $summary);

        // $summary = preg_replace("/\&Alias;/", "alias", $summary);
        $summary = preg_replace("/{@see (" . self::ID_PATTERN . ")}/", "$1", $summary);
        $summary = str_replace(".", '', $summary);
        $summary = preg_replace('/<b>/', '', $summary);
        $summary = preg_replace('/<\/b>/', '', $summary);
        $summary = preg_replace('/<i>/', '', $summary);
        $summary = preg_replace('/<\/i>/', '', $summary);

        //TODO REWRITE THIS
        if (strpos($summary, "(PECL") !== false || strpos($summary, "(PHP ") !== false) {
            $strpos = max(strpos($summary, "\n"), strpos($summary, "<br>") + 4, strpos($summary, "</br>") + 5, strpos($summary, "<br/>") + 5);
            $summary = substr($summary, $strpos);
        }
        return strtolower(trim($summary));
    }

    /**
     * @param Doc|null $doc
     * @param string $function_name
     */
    private static function validateParameters(PHPFunction $function, ?Doc $doc, string $function_name): void
    {
        if ($function_name === "parallel\Sync.__construct") {

            var_dump($function->mutedProblems);
        }
        if ($function->hasMutedProblem(StubProblemType::PARAMETER_TYPE_IS_WRONG_IN_OFICIAL_DOCS)) {
            static::markTestSkipped('function is excluded');
        }
        $docBlock = DocFactoryProvider::getDocFactory()->create($doc->getText());
        $tags = $docBlock->getTagsByName("param");
        foreach ($tags as $parameter) {
            $paramsDataFromDocs = self::$SQLite3->query("select * from params where function_name = '$function_name' and name = '{$parameter->getVariableName()}' ")->fetchArray();
            if ($paramsDataFromDocs !== false) {
                $normalizedDocType = self::normalizeType($paramsDataFromDocs["type"]);
                if ($normalizedDocType === "mixed") {
                    self::markTestSkipped("Skipped for function '$function_name' parameter name '\${$parameter->getVariableName()}'");
                } else {
                    $noramlizedTypeFromStubs = self::normalizeType(self::filterNull($parameter) . "");
                    foreach (explode("|", $normalizedDocType) as $docType) {
                        if ($docType === 'array') {
                            self::assertTrue(str_contains($noramlizedTypeFromStubs, "[]") || str_contains($noramlizedTypeFromStubs, "array"), "no $docType found in type: '$noramlizedTypeFromStubs' parameterName: \${$parameter->getVariableName()}");
                        } else {
                            self::assertTrue(self::hasType($docType, $noramlizedTypeFromStubs), "parameter: $" . $parameter->getVariableName() . "\nfunction name: $function_name doctype:$docType stubstype: $noramlizedTypeFromStubs");
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Tag $parameter
     * @return mixed
     */
    private static function filterNull(Tag $parameter)
    {
        $types = $parameter->getType();
        if ($types instanceof Compound) {
            $types = new Compound(array_filter($types->getIterator()->getArrayCopy(), function ($a) {
                return !$a instanceof Null_;
            }));
        }
        return $types;
    }

    /**
     * @param string $parameterType
     * @return string
     */
    private static function normalizeType(string $parameterType): string
    {
        $strtolower = strtolower(trim($parameterType));
        $types = explode("|", $strtolower);
        $types = self::trunkNamespaces($types);
        return implode("|", $types);
    }

    /**
     * @param mixed $functionsData
     * @param string $docBlockSummary
     * @param string|null $function_name
     */
    private static function checkSummary(mixed $functionsData, string $docBlockSummary, ?string $function_name): void
    {
        $summaryFromOfficialDocs = $functionsData === false ? "" : $functionsData["purpose"];
        $stubs = self::normalizeSummary($docBlockSummary);
        if ($summaryFromOfficialDocs !== '') {
            $officialSummary = self::normalizeSummary($summaryFromOfficialDocs);
            if ($officialSummary !== "description") {

                self::assertEquals($officialSummary, $stubs, "function $function_name");
            }
        }
    }

    /**
     * @param mixed $type
     */
    private static function trunkNameSpace(mixed $type): string
    {
        $explode = explode("\\", $type);
        return $explode[sizeof($explode) - 1];
    }

    /**
     * @param array|bool $types
     */
    private static function trunkNamespaces(array|bool $types): array
    {
        $newTypes = [];
        foreach ($types as $type) {
            $newTypes[] = self::trunkNameSpace($type);
        }
        return $newTypes;
    }

    /**
     * @param string|null $function_name
     * @param PHPFunction $function
     */
    private static function validateReturnType(?string $function_name, PHPFunction $function): void
    {
        $functionsData = self::$SQLite3->query("select * from functions where name = '$function_name'")->fetchArray();
        if ($functionsData !== false) {
            if ($function->hasMutedProblem(StubProblemType::RETURN_TYPE_IS_WRONG_IN_OFICIAL_DOCS)) {
                static::markTestSkipped('function is excluded');
            }
            if ($functionsData !== null) {
                // echo "return type for " . $function_name . " is not found in official docs";
                self::assertEquals($functionsData["return_type"], $function->returnTag . "", "return type mismatch '$function_name'");
            }
        }
    }

    /**
     * @param mixed $docType
     * @param string $noramlizedTypeFromStubs
     * @return bool
     */
    private static function hasType(mixed $docType, string $noramlizedTypeFromStubs): bool
    {
        $types = explode("|", $noramlizedTypeFromStubs);
        foreach ($types as $type) {
            if (self::typesAreEqual($type, $docType)) {
                return true;
            }
        }
        return false;

    }

    /**
     * @param mixed $type
     * @param mixed $docType
     * @return bool
     */
    private static function typesAreEqual(mixed $type, mixed $docType): bool
    {
        if (StubsPhpDocTest::isInteger($docType) && StubsPhpDocTest::isInteger($type)) {
            return true;
        }
        if (StubsPhpDocTest::isBoolean($docType) && StubsPhpDocTest::isBoolean($type)) {
            return true;
        }
        if( StubsPhpDocTest::isFloat($docType) && StubsPhpDocTest::isFloat($type)) {
            return true;
        }
        if( StubsPhpDocTest::isCallable($docType) && $type == "callable") {
            return true;
        }
        return $type === $docType;
    }

    /**
     * @param mixed $docType
     * @return bool
     */
    private static function isBoolean(mixed $docType): bool
    {
        return ($docType === "bool" || $docType === "boolean");
    }

    /**
     * @param mixed $docType
     * @return bool
     */
    private static function isFloat(mixed $docType): bool
    {
        return ($docType === "float" || $docType === "double");
    }

    private static function isInteger(mixed $docType)
    {
        return ($docType === "int" || $docType === "integer");
    }

    private static function isCallable(mixed $docType)
    {
        return ($docType === "call" || $docType === "callable" || $docType === "callback");
    }

}
