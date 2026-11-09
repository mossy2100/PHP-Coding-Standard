<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\PHP;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Warns about `clone` without parentheses, e.g. `clone $a`, or with a space before them, e.g. `clone ($a)`.
 *
 * PER Coding Style 3.1, section 4.7, says `clone()` SHOULD always be called with parentheses, as it's a function as of
 * PHP 8.5. It's a SHOULD, so this is a warning.
 *
 * The fix wraps the operand in parentheses, as `clone` binds more tightly than any operator. It's only applied when the
 * operand is a simple expression: a variable, name or `new` expression, followed by any `->`, `?->`, `::`, `[]` or
 * call. A space before the parentheses is only removed when nothing is dereferenced after them, as
 * `clone ($a)->b` clones `$a->b` before PHP 8.5, but would then clone `$a`.
 */
class RequireCloneParenthesesSniff implements Sniff
{
    public const string CODE_MISSING = 'Missing';

    public const string CODE_SPACE_BEFORE = 'SpaceBefore';

    /**
     * Tokens that can start a simple operand.
     *
     * @var list<int|string>
     */
    private const array OPERAND_START = [
        T_VARIABLE,
        T_STRING,
        T_NAME_QUALIFIED,
        T_NAME_FULLY_QUALIFIED,
        T_NAME_RELATIVE,
        T_SELF,
        T_STATIC,
        T_PARENT,
    ];

    /**
     * Tokens that dereference the expression before them.
     *
     * @var list<int|string>
     */
    private const array DEREFERENCE = [
        T_OBJECT_OPERATOR,
        T_NULLSAFE_OBJECT_OPERATOR,
        T_DOUBLE_COLON,
        T_OPEN_SQUARE_BRACKET,
        T_OPEN_PARENTHESIS,
    ];

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
            if ($next === ($stackPtr + 1)) {
                return;
            }

            $this->processSpaceBefore($phpcsFile, $stackPtr, $next);
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
     * Report the whitespace between `clone` and its parentheses, fixable if nothing is dereferenced after them.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of `clone`.
     * @param int $opener The position of the `(`.
     */
    private function processSpaceBefore(File $phpcsFile, int $stackPtr, int $opener): void
    {
        $tokens = $phpcsFile->getTokens();
        $error = 'Expected no space between clone and "("';
        $closer = $tokens[$opener]['parenthesis_closer'] ?? null;
        $after = is_int($closer) ? $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closer + 1), null, true) : false;
        $dereferenced = $after !== false && in_array($tokens[$after]['code'], self::DEREFERENCE, true);
        $hasComment = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($stackPtr + 1), $opener) !== false;
        if (!is_int($closer) || $dereferenced || $hasComment) {
            $phpcsFile->addWarning($error, $stackPtr, self::CODE_SPACE_BEFORE);
            return;
        }

        $fix = $phpcsFile->addFixableWarning($error, $stackPtr, self::CODE_SPACE_BEFORE);
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        FixerHelper::blankBetween($phpcsFile, $stackPtr, $opener);

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Find the last token of a simple operand: a variable, name or `new` expression, then any dereferences.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the operand's first token.
     * @return int|null The position, or null if the operand isn't simple.
     */
    private function findOperandEnd(File $phpcsFile, int $start): ?int
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$start]['code'] === T_NEW) {
            $end = $this->findNewEnd($phpcsFile, $start);
        } elseif (in_array($tokens[$start]['code'], self::OPERAND_START, true)) {
            $end = $start;
        } else {
            return null;
        }

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

            if (!in_array($code, self::DEREFERENCE, true)) {
                return $end;
            }

            $member = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($next + 1), null, true);
            $end = $member !== false && in_array($tokens[$member]['code'], self::MEMBER, true) ? $member : null;
        }

        return null;
    }

    /**
     * Find the last token of a `new` expression: its argument list's `)`, or an anonymous class's `}`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $newPtr The position of `new`.
     * @return int|null The position, or null if there's no argument list or the class reference isn't simple.
     */
    private function findNewEnd(File $phpcsFile, int $newPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $class = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($newPtr + 1), null, true);
        if ($class === false) {
            return null;
        }

        if ($tokens[$class]['code'] === T_ANON_CLASS) {
            $closer = $tokens[$class]['scope_closer'] ?? null;

            return is_int($closer) ? $closer : null;
        }

        if (!in_array($tokens[$class]['code'], self::OPERAND_START, true)) {
            return null;
        }

        $opener = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($class + 1), null, true);
        if ($opener === false || $tokens[$opener]['code'] !== T_OPEN_PARENTHESIS) {
            return null;
        }

        $closer = $tokens[$opener]['parenthesis_closer'] ?? null;

        return is_int($closer) ? $closer : null;
    }
}
