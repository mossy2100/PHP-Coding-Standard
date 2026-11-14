<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\PHP;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Warns about `clone` without parentheses, e.g. `clone $a`, and reports a space before them, e.g. `clone ($a)`.
 *
 * PER Coding Style 3.1, section 4.7, says `clone()` SHOULD always be called with parentheses, as it's a function as of
 * PHP 8.5. It's a SHOULD, so that's a warning. With the parentheses, it's a function call, and 4.7 says there MUST NOT
 * be a space between a function's name and its opening parenthesis, so `clone ($a)` is an error, as `exit ($a)` is.
 *
 * The fix wraps the operand in parentheses, as `clone` binds more tightly than any operator. It's only applied when the
 * operand is a simple expression: a variable, variable variable, name or `new` expression, followed by any `->`,
 * `?->`, `::`, `[]` or call.
 *
 * Parentheses followed by a dereference don't hold the whole operand. PHP reads `clone($a)->b` as a clone of `$a->b`,
 * so the parentheses only group `$a`. That's reported as PartOfOperand, and fixed to `clone($a->b)`. When what's
 * inside isn't a simple expression, its parentheses stay, so `clone($a + $b)->c` becomes `clone(($a + $b)->c)`.
 */
class RequireCloneParenthesesSniff implements Sniff
{
    public const string CODE_MISSING = 'Missing';

    public const string CODE_SPACE_BEFORE = 'SpaceBefore';

    public const string CODE_PART_OF_OPERAND = 'PartOfOperand';

    /**
     * Tokens that can follow a dereferencing operator.
     *
     * @var list<int|string>
     */
    private const array MEMBER = [T_STRING, T_VARIABLE, T_CLASS];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_CLONE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($next === false) {
            return;
        }

        if ($tokens[$next]['code'] === T_OPEN_PARENTHESIS) {
            $closer = $tokens[$next]['parenthesis_closer'] ?? null;
            if (is_int($closer) && TokenHelper::findCloneArguments($phpcsFile, $stackPtr) === null) {
                $this->processGrouping($phpcsFile, $stackPtr, $next, $closer);

                return;
            }

            if ($next !== ($stackPtr + 1)) {
                $this->processSpaceBefore($phpcsFile, $stackPtr, $next);
            }

            return;
        }

        $error = 'Use parentheses with clone, e.g. "clone($a)", not "clone $a"';
        $end = $this->findOperandEnd($phpcsFile, $next);
        $hasComment = $end !== null
            && $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($stackPtr + 1), ($end + 1)) !== false;
        if ($end === null || $hasComment) {
            $phpcsFile->addWarning($error, $stackPtr, self::CODE_MISSING);
            return;
        }

        $fix = $phpcsFile->addFixableWarning($error, $stackPtr, self::CODE_MISSING);
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        FixerHelper::blankBetween($phpcsFile, $stackPtr, $next);

        $phpcsFile->fixer->addContentBefore($next, '(');
        $phpcsFile->fixer->addContent($end, ')');
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Report parentheses after `clone` that hold only part of what is cloned, as in `clone($a)->b`, which clones
     * `$a->b`. The fix makes them hold all of it: `clone($a->b)`, or `clone(($a + $b)->c)` when the part inside isn't
     * a simple expression.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of `clone`.
     * @param int $opener The position of the `(`.
     * @param int $closer The position of the `)`.
     */
    private function processGrouping(File $phpcsFile, int $stackPtr, int $opener, int $closer): void
    {
        $error = 'The parentheses after clone must hold everything that is cloned, e.g. "clone($a->b)", '
            . 'not "clone($a)->b"';
        // Only a comment between clone and the parentheses would be lost. One further on stays where it is.
        $end = $this->findChainEnd($phpcsFile, $closer);
        $hasComment = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($stackPtr + 1), $opener) !== false;
        if ($end === null || $hasComment) {
            $phpcsFile->addWarning($error, $stackPtr, self::CODE_PART_OF_OPERAND);

            return;
        }

        if (!$phpcsFile->addFixableWarning($error, $stackPtr, self::CODE_PART_OF_OPERAND)) {
            return;
        }

        // A simple expression needs no parentheses of its own, so its closing one moves. Anything else keeps them.
        $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($opener + 1), null, true);
        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($closer - 1), null, true);
        $isSimple = $first !== false && $this->findOperandEnd($phpcsFile, $first) === $last;

        $phpcsFile->fixer->beginChangeset();
        FixerHelper::blankBetween($phpcsFile, $stackPtr, $opener);
        if ($isSimple) {
            $phpcsFile->fixer->replaceToken($closer, '');
        } else {
            $phpcsFile->fixer->addContent($opener, '(');
        }

        $phpcsFile->fixer->addContent($end, ')');
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Report the whitespace between `clone` and its parentheses, as an error.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of `clone`.
     * @param int $opener The position of the `(`.
     */
    private function processSpaceBefore(File $phpcsFile, int $stackPtr, int $opener): void
    {
        $error = 'Expected no space between clone and "("';
        $hasComment = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($stackPtr + 1), $opener) !== false;
        if (!FixerHelper::addFixableErrorIf($phpcsFile, !$hasComment, $error, $stackPtr, self::CODE_SPACE_BEFORE)) {
            return;
        }

        FixerHelper::replaceBetween($phpcsFile, $stackPtr, $opener, '');
    }

    /**
     * Find the last token of a simple operand: a variable, variable variable, name or `new` expression, then any
     * dereferences.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the operand's first token.
     * @return int|null The position, or null if the operand isn't simple.
     */
    private function findOperandEnd(File $phpcsFile, int $start): ?int
    {
        // A variable variable starts with a `$` of its own, as in `$$a` or `${$a}`.
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$start]['code'] === T_DOLLAR) {
            $start = (int) $phpcsFile->findNext([...Tokens::EMPTY_TOKENS, T_DOLLAR], ($start + 1), null, true);
        }

        if ($tokens[$start]['code'] === T_OPEN_CURLY_BRACKET) {
            $end = TokenHelper::findCloser($tokens[$start]);
        } elseif ($tokens[$start]['code'] === T_NEW) {
            $end = TokenHelper::findNewExpressionEnd($phpcsFile, $start);
        } elseif (in_array($tokens[$start]['code'], TokenHelper::CLASS_REFERENCE, true)) {
            $end = $start;
        } else {
            return null;
        }

        return $end === null ? null : $this->findChainEnd($phpcsFile, $end);
    }

    /**
     * Find the last token of the dereferences and calls that follow a token, as in `->b->c()[0]`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $end The position of the token they follow.
     * @return int|null The position, or the token's own if nothing follows, or null if a member's name isn't simple.
     */
    private function findChainEnd(File $phpcsFile, int $end): ?int
    {
        $tokens = $phpcsFile->getTokens();
        while ($end !== null) {
            $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($end + 1), null, true);
            if ($next === false) {
                return $end;
            }

            $code = $tokens[$next]['code'];
            if ($code === T_OPEN_SQUARE_BRACKET || $code === T_OPEN_PARENTHESIS) {
                $closer = TokenHelper::findCloser($tokens[$next]);
                $end = is_int($closer) ? $closer : null;
                continue;
            }

            if (!in_array($code, TokenHelper::DEREFERENCE, true)) {
                return $end;
            }

            // A member's name may be in braces, as in `->{$name}`, or a variable variable, as in `->$$name`.
            $member = $phpcsFile->findNext([...Tokens::EMPTY_TOKENS, T_DOLLAR], ($next + 1), null, true);
            if ($member !== false && $tokens[$member]['code'] === T_OPEN_CURLY_BRACKET) {
                $closer = TokenHelper::findCloser($tokens[$member]);
                $end = is_int($closer) ? $closer : null;
                continue;
            }

            $end = $member !== false && in_array($tokens[$member]['code'], self::MEMBER, true) ? $member : null;
        }

        return null;
    }
}
