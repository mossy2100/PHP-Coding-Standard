<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use Closure;
use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
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
 * - An expression split inside parentheses MUST have its first condition on the next line, with each subsequent line
 *   indented at least once. The fix moves the first condition, or indents the line once past the `case`.
 * - A non-empty case that continues into the next one MUST have a comment saying so. The parent only checks a `case`,
 *   so this also checks a `default` that isn't last.
 *
 * To find a terminating statement nested in if/else, try/catch or another switch, it calls the parent's private
 * findNestedTerminator() through Closure::bind, rather than copying it. After a PHP_CodeSniffer upgrade, check that
 * the method still exists with the same signature, as a private method can change without notice.
 *
 * A switch with a `case` or `default` body wrapped in braces is reported here, with the parent's WrongOpener code, and
 * the parent isn't run on it. This works around a bug in PSR2.ControlStructures.SwitchDeclaration (PHP_CodeSniffer
 * 4.0.4): after a braced body, it reads a scope_condition the next label's closer doesn't have, which aborts the
 * file's checks, and stops phpcbf altogether. So such a switch gets no other check until the braces are removed.
 *
 * Everything else is left to the parent. That includes the `case` line, which MUST consist only of the keyword, the
 * condition and a colon. The parent's BodyOnNextLine check reports code there, and a blank line straight after the
 * label, which section 5.2's example doesn't have. A comment on the line is allowed.
 */
class SwitchDeclarationSniff extends Psr2Sniff
{
    public const string CODE_MISSING_TERMINATOR = 'MissingTerminator';

    public const string CODE_MULTI_LINE_CONDITION_PARENTHESES = 'MultiLineConditionParentheses';

    public const string CODE_MULTI_LINE_CONDITION_CLOSER = 'MultiLineConditionCloser';

    public const string CODE_MULTI_LINE_CONDITION_FIRST_LINE = 'MultiLineConditionFirstLine';

    public const string CODE_MULTI_LINE_CONDITION_INDENT = 'MultiLineConditionIndent';

    public const string CODE_TERMINATING_COMMENT = 'TerminatingComment';

    /**
     * The parent's code for a body wrapped in braces.
     */
    public const string CODE_WRONG_OPENER = 'WrongOpener';

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
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
        if (!is_int($closer)) {
            parent::process($phpcsFile, $stackPtr);
            return;
        }

        $cases = $this->findCases($phpcsFile, $stackPtr, $closer);
        if ($this->reportBracedBodies($phpcsFile, $cases)) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
        foreach ($cases as $index => $case) {
            if ($tokens[$case]['code'] === T_CASE) {
                $this->checkMultiLineCondition($phpcsFile, $case);
            } elseif (isset($cases[($index + 1)])) {
                $this->checkDefaultFallThrough($phpcsFile, $case, $cases[($index + 1)]);
            }
        }

        if ($cases !== []) {
            $this->checkLastCase($phpcsFile, end($cases), $closer);
        }
    }

    /**
     * Report each `case` or `default` whose body is wrapped in braces, as the parent does, with its WrongOpener code.
     *
     * @param File $phpcsFile The file being scanned.
     * @param list<int> $cases The positions of the switch's `case` and `default` keywords.
     * @return bool True if any was reported, so the parent, which can crash on such a switch, isn't run.
     */
    private function reportBracedBodies(File $phpcsFile, array $cases): bool
    {
        $tokens = $phpcsFile->getTokens();
        $hasBracedBody = false;
        foreach ($cases as $case) {
            $opener = $tokens[$case]['scope_opener'] ?? null;
            if (!is_int($opener) || $tokens[$opener]['code'] !== T_OPEN_CURLY_BRACKET) {
                continue;
            }

            $phpcsFile->addError(
                '%s statements must not use a braced block after the colon',
                $case,
                self::CODE_WRONG_OPENER,
                [strtoupper($tokens[$case]['content'])],
            );
            $hasBracedBody = true;
        }

        return $hasBracedBody;
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
     * Check that a `default` followed by another case, if not empty, ends with a terminating statement or a comment.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $default The position of `default`.
     * @param int $next The position of the next `case` or `default`.
     */
    private function checkDefaultFallThrough(File $phpcsFile, int $default, int $next): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$default]['scope_opener'] ?? null;
        if (!is_int($opener) || $tokens[$opener]['code'] !== T_COLON) {
            return;
        }

        $last = $phpcsFile->findPrevious(T_WHITESPACE, ($next - 1), $default, true);
        if ($last === false || $last === $opener || isset(Tokens::COMMENT_TOKENS[$tokens[$last]['code']])) {
            return;
        }

        if ($this->endsWithTerminator($phpcsFile, $default, $opener, $next)) {
            return;
        }

        $phpcsFile->addError(
            'There must be a comment when fall-through is intentional in a non-empty case body',
            $default,
            self::CODE_TERMINATING_COMMENT,
        );
    }

    /**
     * Check whether a case's body ends with a terminating statement.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of the `case` or `default`.
     * @param int $opener The position of the case's colon.
     * @param int $end The position to stop looking at.
     * @return bool True if it does.
     */
    private function endsWithTerminator(File $phpcsFile, int $case, int $opener, int $end): bool
    {
        $tokens = $phpcsFile->getTokens();

        // The tokenizer ends a case's scope at a `break`, `return` or similar at its own level.
        $caseCloser = $tokens[$case]['scope_closer'] ?? null;
        if (
            is_int($caseCloser)
            && $caseCloser < $end
            && in_array($tokens[$caseCloser]['code'], self::TERMINATORS, true)
        ) {
            return true;
        }

        // Otherwise, the parent's private search, which follows if/else, try/catch and nested switch blocks.
        $findNestedTerminator = Closure::bind(
            fn(File $file, int $start, int $stop): int|bool => $this->findNestedTerminator($file, $start, $stop),
            $this,
            Psr2Sniff::class,
        );

        return $findNestedTerminator($phpcsFile, ($opener + 1), $end) !== false;
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

        if ($this->endsWithTerminator($phpcsFile, $case, $opener, $closer)) {
            return;
        }

        // The fix adds `break;` on a line of its own before the `}`, so the `}` must start its line.
        $error = 'The last case in a switch must end with a break, return or similar terminating statement';
        $beforeCloser = $phpcsFile->findPrevious(T_WHITESPACE, ($closer - 1), null, true);
        $isFixable = $beforeCloser !== false && $tokens[$beforeCloser]['line'] !== $tokens[$closer]['line'];
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $case, self::CODE_MISSING_TERMINATOR)) {
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
     * Check that a multi-line case condition is wrapped in parentheses, with its first condition on the next line, its
     * lines indented, and `):` on a line of their own.
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

        $this->checkFirstConditionLine($phpcsFile, $first, $last, $indent);
        $this->checkConditionIndent($phpcsFile, $case, $first, $last);

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
        FixerHelper::blankBetween($phpcsFile, $beforeCloser, $last);

        $phpcsFile->fixer->addContentBefore($last, $phpcsFile->eolChar . $indent);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check that a split condition's first condition is on the line after the opening parenthesis.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the opening parenthesis.
     * @param int $closer The position of the closing parenthesis.
     * @param string $indent The indent of the `case`.
     */
    private function checkFirstConditionLine(File $phpcsFile, int $opener, int $closer, string $indent): void
    {
        $tokens = $phpcsFile->getTokens();
        $condition = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($opener + 1), $closer, true);
        if ($condition === false || $tokens[$condition]['line'] !== $tokens[$opener]['line']) {
            return;
        }

        $error = 'The first condition of a multi-line case condition must be on the line after the opening parenthesis';
        $next = $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), $closer, true);
        if ($next !== $condition) {
            $phpcsFile->addError($error, $condition, self::CODE_MULTI_LINE_CONDITION_FIRST_LINE);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $condition, self::CODE_MULTI_LINE_CONDITION_FIRST_LINE);
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        FixerHelper::blankBetween($phpcsFile, $opener, $condition);

        $phpcsFile->fixer->addContent($opener, $phpcsFile->eolChar . $indent . str_repeat(' ', LayoutHelper::INDENT));
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check that each line inside a split condition's parentheses is indented at least once past the `case`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of `case`.
     * @param int $opener The position of the opening parenthesis.
     * @param int $closer The position of the closing parenthesis.
     */
    private function checkConditionIndent(File $phpcsFile, int $case, int $opener, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        $minimum = ($tokens[$case]['column'] - 1 + LayoutHelper::INDENT);
        $skipped = Tokens::HEREDOC_TOKENS + Tokens::STRING_TOKENS + [T_WHITESPACE => T_WHITESPACE];
        for ($i = ($opener + 1); $i < $closer; $i++) {
            $isLineStart = $tokens[$i]['line'] !== $tokens[($i - 1)]['line'];
            $code = $tokens[$i]['code'];
            if ($isLineStart && $code === T_WHITESPACE) {
                $code = $tokens[($i + 1)]['code'];
                if ($code === T_WHITESPACE || $tokens[($i + 1)]['line'] !== $tokens[$i]['line']) {
                    continue;
                }

                $isIndented = $tokens[($i + 1)]['column'] - 1 >= $minimum;
                $i++;
            } elseif ($isLineStart) {
                $isIndented = $minimum === 0;
            } else {
                continue;
            }

            if ($isIndented || $i >= $closer || isset($skipped[$code])) {
                continue;
            }

            $error = 'Each line of a multi-line case condition must be indented at least once';
            $fix = $phpcsFile->addFixableError($error, $i, self::CODE_MULTI_LINE_CONDITION_INDENT);
            if ($fix) {
                $padding = str_repeat(' ', $minimum);
                if ($tokens[($i - 1)]['code'] === T_WHITESPACE && $tokens[($i - 1)]['column'] === 1) {
                    $phpcsFile->fixer->replaceToken(($i - 1), $padding);
                } else {
                    $phpcsFile->fixer->addContentBefore($i, $padding);
                }
            }
        }
    }
}
