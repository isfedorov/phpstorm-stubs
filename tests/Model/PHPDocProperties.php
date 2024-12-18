<?php

namespace StubTests\Model;

use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use phpDocumentor\Reflection\DocBlock\Tags\Link;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\See;
use phpDocumentor\Reflection\DocBlock\Tags\Since;
use phpDocumentor\Reflection\DocBlock\Tags\Template;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use StubTests\Model\Tags\RemovedTag;

class PHPDocProperties
{
    /**
     * @var Link[]
     */
    public $linkTags = [];

    /**
     * @var string
     */
    public $phpdoc = '';

    /**
     * @var See[]
     */
    public $seeTags = [];

    /**
     * @var Since[]
     */
    public $sinceTags = [];

    /**
     * @var Deprecated[]
     */
    public $deprecatedTags = [];

    /**
     * @var RemovedTag[]
     */
    public $removedTags = [];

    /**
     * @var Param[]
     */
    public $paramTags = [];

    /**
     * @var Return_[]
     */
    public $returnTags = [];

    /**
     * @var Var_[]
     */
    public $varTags = [];

    /**
     * @var string[]
     */
    public $tagNames = [];

    /**
     * @var bool
     */
    public $hasInheritDocTag = false;

    /**
     * @var bool
     */
    public $hasInternalMetaTag = false;

    /**
     * @var list<Template>
     */
    public $templateTypes = [];

}
