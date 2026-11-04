<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\ControlStructures;

use Closure;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\ControlStructures\SwitchDeclarationSniff as Psr2Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks `switch` statements, adding the rules in PER Coding Style 3.1 that PSR2.ControlStructures.SwitchDeclaration
 * doesn't check.
 *
 * PER Coding Style 3.1, section 5.2, says:
 * - Every non-empty case MUST end with a terminating statement, such as `break` or `return`, "even if they are the
 *   final one". The parent only checks a case that's followed by another. The fix adds `break;` before the `}`.
 * - A multi-line case condition MUST be wrapped in parentheses, and MUST end with a line containing only `):`. The fix
 *   adds the parentheses, or moves the `)` to its own line.
 *
 * Everything else is left to the parent.
 */
class SwitchDeclarationSniff extends Psr2Sniff
{
    public const string CODE_MISSING_TERMINATOR = 'MissingTerminator';

    public const string CODE_MULTI_LINE_CONDITION_PARENTHESES = 'MultiLineConditionParentheses';

    public const string CODE_MULTI_LINE_CONDITION_CLOSER = 'MultiLineConditionCloser';

    /**
     * Tokens that end a case, as the tokenizer and the parent define them.
     *
     * @var list<int|string>
     */
    private const array TERMINATORS = [T_BREAK, T_RETURN, T_CONTINUE, T_THROW, T_EXIT, T_GOTO];

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        parent::process($phpcsFile, $stackPtr);

        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
        if (!is_int($closer)) {
            return;
        }

        $cases = $this->findCases($phpcsFile, $stackPtr, $closer);
        foreach ($cases as $case) {
            if ($tokens[$case]['code'] === T_CASE) {
                $this->checkMultiLineCondition($phpcsFile, $case);
            }
        }

        if ($cases !== []) {
            $this->checkLastCase($phpcsFile, end($cases), $closer);
        }
    }

    /**
     * Find the `case` and `default` keywords that belong to a switch, and not to a nested one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $switch The position of `switch`.
     * @param int $closer The position of the switch's `}`.
     * @return list<int> The positions.
     */
    private function findCases(File $phpcsFile, int $switch, int $closer): array
    {
        $tokens = $phpcsFile->getTokens();
        $cases = [];
        for ($i = ($switch + 1); $i < $closer; $i++) {
            $isCase = $tokens[$i]['code'] === T_CASE || $tokens[$i]['code'] === T_DEFAULT;
            if ($isCase && array_key_last($tokens[$i]['conditions']) === $switch) {
                $cases[] = $i;
            }
        }

        return $cases;
    }

    /**
     * Check that the last case, if not empty, ends with a terminating statement.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of the last `case` or `default`.
     * @param int $closer The position of the switch's `}`.
     */
    private function checkLastCase(File $phpcsFile, int $case, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$case]['scope_opener'] ?? null;
        if (!is_int($opener) || $tokens[$opener]['code'] !== T_COLON) {
            return;
        }

        if ($phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($opener + 1), $closer, true) === false) {
            return;
        }

        // The tokenizer ends a case's scope at a `break`, `return` or similar at its own level.
        $caseCloser = $tokens[$case]['scope_closer'] ?? null;
        if (
            is_int($caseCloser)
            && $caseCloser < $closer
            && in_array($tokens[$caseCloser]['code'], self::TERMINATORS, true)
        ) {
            return;
        }

        // Otherwise, the parent's private search, which follows if/else, try/catch and nested switch blocks.
        $findNestedTerminator = Closure::bind(
            fn(File $file, int $start, int $end): int|bool => $this->findNestedTerminator($file, $start, $end),
            $this,
            Psr2Sniff::class,
        );
        if ($findNestedTerminator($phpcsFile, ($opener + 1), $closer) !== false) {
            return;
        }

        // The fix adds `break;` on a line of its own before the `}`, so the `}` must start its line.
        $error = 'The last case in a switch must end with a break, return or similar terminating statement';
        $beforeCloser = $phpcsFile->findPrevious(T_WHITESPACE, ($closer - 1), null, true);
        if ($beforeCloser === false || $tokens[$beforeCloser]['line'] === $tokens[$closer]['line']) {
            $phpcsFile->addError($error, $case, self::CODE_MISSING_TERMINATOR);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $case, self::CODE_MISSING_TERMINATOR);
        if (!$fix) {
            return;
        }

        $lineStart = $closer;
        while ($tokens[($lineStart - 1)]['line'] === $tokens[$closer]['line']) {
            $lineStart--;
        }

        $indent = str_repeat(' ', ($tokens[$case]['column'] - 1 + $this->indent));
        $phpcsFile->fixer->addContentBefore($lineStart, $indent . 'break;' . $phpcsFile->eolChar);
    }

    /**
     * Check that a multi-line case condition is wrapped in parentheses, with `):` on a line of their own.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of `case`.
     */
    private function checkMultiLineCondition(File $phpcsFile, int $case): void
    {
        $tokens = $phpcsFile->getTokens();
        $colon = $tokens[$case]['scope_opener'] ?? null;
        if (
            !is_int($colon)
            || $tokens[$colon]['code'] !== T_COLON
            || $tokens[$colon]['line'] === $tokens[$case]['line']
        ) {
            return;
        }

        $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($case + 1), $colon, true);
        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($colon - 1), $case, true);
        if ($first === false || $last === false) {
            return;
        }

        $indent = str_repeat(' ', ($tokens[$case]['column'] - 1));
        $hasTrailingComment = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($last + 1), $colon) !== false;
        $isWrapped = $tokens[$first]['code'] === T_OPEN_PARENTHESIS
            && ($tokens[$first]['parenthesis_closer'] ?? null) === $last;
        if (!$isWrapped) {
            $error = 'A multi-line case condition must be wrapped in parentheses';
            if ($hasTrailingComment) {
                $phpcsFile->addError($error, $case, self::CODE_MULTI_LINE_CONDITION_PARENTHESES);
                return;
            }

            $fix = $phpcsFile->addFixableError($error, $case, self::CODE_MULTI_LINE_CONDITION_PARENTHESES);
            if (!$fix) {
                return;
            }

            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->addContentBefore($first, '(');
            $phpcsFile->fixer->addContent($last, $phpcsFile->eolChar . $indent . ')');
            $phpcsFile->fixer->endChangeset();
            return;
        }

        $beforeCloser = $phpcsFile->findPrevious(T_WHITESPACE, ($last - 1), $case, true);
        if ($beforeCloser === false || $tokens[$beforeCloser]['line'] !== $tokens[$last]['line']) {
            return;
        }

        $error = 'The closing parenthesis and colon of a multi-line case condition must be on a line of their own';
        if ($tokens[$beforeCloser]['code'] === T_COMMENT || $hasTrailingComment) {
            $phpcsFile->addError($error, $last, self::CODE_MULTI_LINE_CONDITION_CLOSER);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $last, self::CODE_MULTI_LINE_CONDITION_CLOSER);
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($beforeCloser + 1); $i < $last; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContentBefore($last, $phpcsFile->eolChar . $indent);
        $phpcsFile->fixer->endChangeset();
    }
}
