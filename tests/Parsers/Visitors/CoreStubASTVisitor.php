<?php
declare(strict_types=1);

namespace StubTests\Parsers\Visitors;

use JetBrains\PhpStorm\Pure;
use StubTests\Model\StubsContainer;

class CoreStubASTVisitor extends ASTVisitor
{
    #[Pure]
    public function __construct(StubsContainer $stubs, $shouldSuitCurrentPhpVersion)
    {
        parent::__construct($stubs, shouldSuitCurrentPhpVersion: $shouldSuitCurrentPhpVersion);
        $this->isStubCore = true;
    }
}
