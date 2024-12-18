<?php

namespace StubTests\Model;

use Exception;
use PhpParser\Node;
use Reflector;
use stdClass;
use StubTests\Parsers\DocFactoryProvider;
use StubTests\Parsers\Helpers\AttributesHelper;
use StubTests\Parsers\Helpers\PhpDocHelpers;
use function array_key_exists;
use function in_array;

abstract class BasePHPElement implements PHPDocumentable
{
    /** @var PHPDocProperties|null */
    private $phpdocProperties = null;

    /** @var StubsSpecificAttributes|null */
    private $stubSpecificProperties = null;

    /** @var string|null */
    public $name = null;

    /** @var array|null  */
    public $mutedProblems = null;

    /** @var string|null */
    public $fqnBasedId = null;

    /** @var bool */
    public $isDeprecated = false;

    /**
     * @param Reflector $reflectionObject
     * @return static
     */
    abstract public function readObjectFromReflection($reflectionObject);

    /**
     * @param Node $node
     * @return static
     */
    abstract public function readObjectFromStubNode($node);

    /**
     * @param stdClass|array $jsonData
     */
    abstract public function readMutedProblems($jsonData);

    public function collectTags(Node $node)
    {
        $this->phpdocProperties = new PHPDocProperties();
        if ($node->getDocComment() !== null) {
            try {
                $text = $node->getDocComment()->getText();
                $text = preg_replace("/int\<\w+,\s*\w+\>/", "int", $text);
                $text = preg_replace("/callable\(\w+(,\s*\w+)*\)(:\s*\w*)?/", "callable", $text);
                $this->phpdocProperties->phpdoc = $text;
                $phpDoc = DocFactoryProvider::getDocFactory()->create($text);
                $tags = $phpDoc->getTags();
                foreach ($tags as $tag) {
                    $this->phpdocProperties->tagNames[] = $tag->getName();
                    $this->phpdocProperties->paramTags = $phpDoc->getTagsByName('param');
                    $this->phpdocProperties->returnTags = $phpDoc->getTagsByName('return');
                    $this->phpdocProperties->varTags = $phpDoc->getTagsByName('var');
                    $this->phpdocProperties->linkTags = $phpDoc->getTagsByName('link');
                    $this->phpdocProperties->seeTags = $phpDoc->getTagsByName('see');
                    $this->phpdocProperties->sinceTags = $phpDoc->getTagsByName('since');
                    $this->phpdocProperties->deprecatedTags = $phpDoc->getTagsByName('deprecated');
                    $this->phpdocProperties->removedTags = $phpDoc->getTagsByName('removed');
                    $this->phpdocProperties->hasInternalMetaTag = $phpDoc->hasTag('meta');
                    $this->phpdocProperties->templateTypes += $phpDoc->getTagsByName('template');
                }
                $this->phpdocProperties->hasInheritDocTag = PhpDocHelpers::hasInheritDocLikeTag($phpDoc);
            } catch (Exception $e) {
                $this->getPhpdocProperties()->phpDocParsingError = $e;
            }
        }
    }

    /**
     * @param int $stubProblemType
     *
     * @return bool
     */
    public function hasMutedProblem($stubProblemType)
    {
        if (!empty($this->mutedProblems) && array_key_exists($stubProblemType, $this->mutedProblems)) {
            if (in_array('ALL', $this->mutedProblems[$stubProblemType], true) ||
                in_array((float)getenv('PHP_VERSION'), $this->mutedProblems[$stubProblemType], true)) {
                return true;
            }
        }

        return false;
    }

    public function checkDeprecation($node)
    {
        $this->isDeprecated = AttributesHelper::isDeprecatedByAttribute($node) || $this->isDeprecatedByPhpDoc();
    }

    public function getPhpdocProperties(): ?PHPDocProperties
    {
        return $this->phpdocProperties;
    }

    public function getOrCreateStubSpecificProperties(): ?StubsSpecificAttributes
    {
        if ($this->stubSpecificProperties === null) {
            $this->stubSpecificProperties = new StubsSpecificAttributes();
        }
        return $this->stubSpecificProperties;
    }

    /**
     * @return bool
     */
    public function deprecatedTagSuitsCurrentPHPVersion(): bool
    {
        foreach ($this->phpdocProperties->deprecatedTags as $deprecatedTag) {
            return $deprecatedTag->getVersion() !== null && (float)$deprecatedTag->getVersion() <= (float)getenv('PHP_VERSION');
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isDeprecatedByPhpDoc(): bool
    {
        return !empty($this->phpdocProperties->deprecatedTags) && $this->deprecatedTagSuitsCurrentPHPVersion();
    }

}
