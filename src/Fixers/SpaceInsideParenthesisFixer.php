<?php

namespace Oyova\PhpCsFixer\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class SpaceInsideParenthesisFixer extends AbstractFixer
{
    use FixerName;

    protected $singleLineWhitespaceOptions = " \t";

    protected function str_contains(string $haystack, string $needle): bool
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There MUST be a space after the opening parenthesis and a space before the closing parenthesis.',
            [
                new CodeSample('
                    <?php

                    class Foo
                    {
                        public static function bar( $foo, $bar )
                        {
                           return false;
                        }
                    }

                    function foo( $bar, $foo )
                    {
                        return false;
                    }
                '),
            ]
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound('(');
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (! $token->equals('(')) {
                continue;
            }

            // don't process if the next token is `)`
            $nextMeaningfulTokenIndex = $tokens->getNextMeaningfulToken($index);

            if ($tokens[$nextMeaningfulTokenIndex]->getContent() === ')') {
                continue;
            }

            $endParenthesisIndex = $tokens->findBlockEnd(
                Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
                $index
            );

            $afterParenthesisIndex = $tokens->getNextNonWhitespace(
                $endParenthesisIndex
            );
            $afterParenthesisToken = $tokens[$afterParenthesisIndex];

            if ($afterParenthesisToken->isGivenKind(CT::T_USE_LAMBDA)) {
                $useStartParenthesisIndex = $tokens->getNextTokenOfKind(
                    $afterParenthesisIndex,
                    ['(']
                );
                $useEndParenthesisIndex = $tokens->findBlockEnd(
                    Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
                    $useStartParenthesisIndex
                );

                // add single-line edge whitespaces inside use parentheses
                $this->fixParenthesisInnerEdge(
                    $tokens,
                    $useStartParenthesisIndex,
                    $useEndParenthesisIndex
                );
            }

            // add single-line edge whitespaces inside parameters list parentheses
            $this->fixParenthesisInnerEdge(
                $tokens,
                $index,
                $endParenthesisIndex
            );
        }
    }

    protected function fixParenthesisInnerEdge(
        Tokens $tokens,
        $start,
        $end
    ): void {
        // add single-line whitespace before )
        if (
            ! $tokens[$end - 1]->isWhitespace($this->singleLineWhitespaceOptions) && ! $this->str_contains($tokens[$end - 1]->getContent(), "\n")
        ) {
            $tokens->ensureWhitespaceAtIndex($end, 0, ' ');
        }

        // add single-line whitespace after (
        if (
            ! $tokens[$start + 1]->isWhitespace($this->singleLineWhitespaceOptions) && ! $this->str_contains($tokens[$start + 1]->getContent(), "\n")
        ) {
            $tokens->ensureWhitespaceAtIndex($start, 1, ' ');
        }
    }
}
