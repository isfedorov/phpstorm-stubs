<?php
declare(strict_types=1);

namespace StubTests\CodeStyle;

use JetBrains\PhpStorm\Pure;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class BracesOneLineFixer implements FixerInterface
{
    public function isCandidate(Tokens $tokens): bool
    {
        return true;
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->equals('{')) {
                continue;
            }
            $openBraceIndex = $index;
            $potentialClosingBraceIndex = $tokens->getNextMeaningfulToken($index);

            if ($potentialClosingBraceIndex !== null && $tokens[$potentialClosingBraceIndex]->equals('}')) {
                $this->convertBracesToOneLine($tokens, $openBraceIndex, $potentialClosingBraceIndex);
            }
        }
    }

    public function getName(): string
    {
        return 'PhpStorm/braces_one_line';
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function supports(SplFileInfo $file): bool
    {
        return true;
    }

    #[Pure]
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            "Braces of empty function's body should be placed on the same line",
            [
                new CodeSample(
                    <<<PHP
<?php
declare(strict_types=1);
function foo() {}
PHP
                ),
            ]
        );
    }

    private function convertBracesToOneLine(Tokens $tokens, int $openBraceIndex, int $closingBraceIndex): void
    {
        $beforeBraceSymbolIndex = $tokens->getPrevNonWhitespace($openBraceIndex);
        if ($beforeBraceSymbolIndex !== null) {
            $this->removeEmptyBlockWithBraces($beforeBraceSymbolIndex, $closingBraceIndex, $tokens);
        }
        $tokens->insertAt($beforeBraceSymbolIndex + 1, new Token(' '));
        $tokens->insertAt($beforeBraceSymbolIndex + 2, new Token('{'));
        $tokens->insertAt($beforeBraceSymbolIndex + 3, new Token('}'));
    }

    private function removeEmptyBlockWithBraces(int $beforeBraceIndex, int $closingBraceIndex, Tokens $tokens): void
    {
        for ($i = $beforeBraceIndex + 1; $i <= $closingBraceIndex; $i++) {
            $tokens->clearAt($i);
        }
    }
}
