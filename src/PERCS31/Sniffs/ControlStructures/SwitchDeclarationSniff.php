<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks `switch` statements, with the rules in PER Coding Style 3.1, section 5.2.
 *
 * This is a fork of PSR2.ControlStructures.SwitchDeclaration from PHP_CodeSniffer 4.0.4, rewritten in this package's
 * style. Its codes and messages are the original's, apart from two codes renamed for clarity. It isn't a subclass, as
 * the original's checks are all inside one long process(), and two of its problems are in the middle of it:
 * - It reports any block straight after a `case` or `default` label, as WrongOpener. Section 5.2 says "The `case` body
 *   MUST NOT be wrapped in `{}`", and a block followed by more of the body, even just `break`, doesn't wrap it.
 * - After a braced body, it reads a scope_condition the next label's closer doesn't have, which aborts the file's
 *   checks, and stops phpcbf altogether.
 *
 * Every change from the original is marked with a "PERCS31:" comment. They are:
 * - A body is reported as wrapped in braces, as BodyInBraces, only when a block starts straight after the label, and
 *   its `}` is followed by the next label or the switch's `}`. A block followed by anything else is part of the body,
 *   and the case gets every other check, measured from its colon.
 * - The closer's scope_condition is checked before it's read, which fixes the crash.
 * - The last case, if not empty, MUST end with a terminating statement, such as `break` or `return`, "even if they are
 *   the final one". The original only checks a case that's followed by another. The fix adds `break;` before the `}`.
 * - A non-empty `default` that falls through needs a comment, as a `case` does. The original only checks a `case`.
 * - A multi-line case condition MUST be wrapped in parentheses, and MUST end with a line containing only `):`, level
 *   with the `case`, as the example shows. A `):` indented too little is left to PERCS31.WhiteSpace.ScopeIndent. Its
 *   first condition goes on the line after `(`, and each line is indented at least once. The fixes add the parentheses,
 *   move the `)` or the first condition, or indent the line.
 * - Code on a label's line, after its colon, is reported as StatementOnLabelLine. A comment there is allowed. The
 *   original's BodyOnNextLine codes report that, and a blank line after the label too, which section 5.2 doesn't
 *   forbid. The PERCS31 ruleset excludes them, and Jumbo uses them in place of StatementOnLabelLine.
 * - Two codes are renamed. The original's WrongOpener, for a body wrapped in braces, is BodyInBraces, and its message
 *   follows section 5.2's words. Its WrongOpenercase and WrongOpenerdefault, for a label that ends in `;` or `?>`, are
 *   MissingColon.
 *
 * To update the fork to a new PHP_CodeSniffer release, compare the original with the code here that isn't marked, and
 * run the tests. Remove the fork once upstream handles both problems.
 */
class SwitchDeclarationSniff implements Sniff
{
    public const string CODE_MISSING_TERMINATOR = 'MissingTerminator';

    public const string CODE_MULTI_LINE_CONDITION_PARENTHESES = 'MultiLineConditionParentheses';

    public const string CODE_MULTI_LINE_CONDITION_CLOSER = 'MultiLineConditionCloser';

    public const string CODE_MULTI_LINE_CONDITION_FIRST_LINE = 'MultiLineConditionFirstLine';

    public const string CODE_MULTI_LINE_CONDITION_INDENT = 'MultiLineConditionIndent';

    public const string CODE_MULTI_LINE_CONDITION_CLOSER_INDENT = 'MultiLineConditionCloserIndent';

    public const string CODE_TERMINATING_COMMENT = 'TerminatingComment';

    public const string CODE_BODY_IN_BRACES = 'BodyInBraces';

    public const string CODE_MISSING_COLON = 'MissingColon';

    public const string CODE_STATEMENT_ON_LABEL_LINE = 'StatementOnLabelLine';

    public const string CODE_SPACING_AFTER_CASE = 'SpacingAfterCase';

    public const string CODE_BREAK_NOT_NEW_LINE = 'BreakNotNewLine';

    public const string CODE_BREAK_INDENT = 'BreakIndent';

    /**
     * Tokens which can terminate a case.
     *
     * @var list<int|string>
     */
    private const array TERMINATORS = [T_RETURN, T_BREAK, T_CONTINUE, T_THROW, T_EXIT, T_GOTO];

    /**
     * The number of spaces code should be indented.
     */
    public int $indent = 4;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_SWITCH];
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

        // We can't process a switch unless we know where it starts and ends.
        $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
        if (!isset($tokens[$stackPtr]['scope_opener']) || !is_int($closer)) {
            return;
        }

        // PERCS31: each label is checked up to the next one, or the switch's `}`, so a braced body and a body that
        // shares its closer with the next case are measured the same way.
        $cases = $this->findCases($phpcsFile, $stackPtr, $closer);
        foreach ($cases as $index => $case) {
            $this->checkLabel($phpcsFile, $case, $cases[($index + 1)] ?? $closer, isset($cases[($index + 1)]));
        }
    }

    /**
     * Check a `case` or `default` label and its body.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of the `case` or `default` keyword.
     * @param int $end The position of the next label, or of the switch's `}` if this is the last.
     * @param bool $hasNextLabel Whether `$end` is another label.
     */
    private function checkLabel(File $phpcsFile, int $case, int $end, bool $hasNextLabel): void
    {
        $tokens = $phpcsFile->getTokens();
        $type = $tokens[$case]['code'] === T_DEFAULT ? 'default' : 'case';

        $this->checkKeywordCase($phpcsFile, $case, $type);

        if (
            $type === 'case'
            && ($tokens[($case + 1)]['code'] !== T_WHITESPACE || $tokens[($case + 1)]['content'] !== ' ')
        ) {
            $error = 'CASE keyword must be followed by a single space';
            if ($phpcsFile->addFixableError($error, $case, self::CODE_SPACING_AFTER_CASE)) {
                if ($tokens[($case + 1)]['code'] !== T_WHITESPACE) {
                    $phpcsFile->fixer->addContent($case, ' ');
                } else {
                    $phpcsFile->fixer->replaceToken(($case + 1), ' ');
                }
            }
        }

        $opener = $tokens[$case]['scope_opener'] ?? null;
        if (!is_int($opener)) {
            return;
        }

        // PERCS31: a block straight after the label is only an error when it's the whole body. Otherwise the case is
        // checked from its colon, as any other.
        $colon = $this->findColon($phpcsFile, $case);
        if ($colon === null) {
            $this->reportMissingColon($phpcsFile, $case, $opener, $type);
            return;
        }

        if ($tokens[$opener]['code'] === T_OPEN_CURLY_BRACKET && $this->wrapsWholeBody($phpcsFile, $opener)) {
            $phpcsFile->addError(
                'The %s body must not be wrapped in braces',
                $case,
                self::CODE_BODY_IN_BRACES,
                [strtoupper($type)],
            );
            return;
        }

        $this->checkColonAndBody($phpcsFile, $case, $colon, $end, $type);
        $this->checkTerminatorLayout($phpcsFile, $case, $opener, $end);
        $this->checkStatementOnLabelLine($phpcsFile, $case, $colon);
        if ($type === 'case') {
            $this->checkMultiLineCondition($phpcsFile, $case, $colon);
        }

        // PERCS31: a `default` that isn't last is checked for fall-through too, and the last label for a terminating
        // statement.
        if ($hasNextLabel) {
            $this->checkFallThrough($phpcsFile, $case, $colon, $end);
        } else {
            $this->checkLastCase($phpcsFile, $case, $colon, $end);
        }
    }

    /**
     * Report a `case` or `default` keyword that isn't in lower case.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of the keyword.
     * @param string $type The kind of label, `case` or `default`.
     */
    private function checkKeywordCase(File $phpcsFile, int $case, string $type): void
    {
        $tokens = $phpcsFile->getTokens();
        $content = (string) $tokens[$case]['content'];
        $expected = strtolower($content);
        if ($content === $expected) {
            return;
        }

        $error = '%3$s keyword must be lowercase; expected "%1$s" but found "%2$s"';
        $data = [$expected, $content, strtoupper($type)];
        if ($phpcsFile->addFixableError($error, $case, "{$type}NotLower", $data)) {
            $phpcsFile->fixer->replaceToken($case, $expected);
        }
    }

    /**
     * Find a label's colon: its scope opener, or the token before a block that the tokenizer took for the opener.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of the `case` or `default` keyword.
     * @return int|null The position of the colon, or null if the label doesn't end in one.
     */
    private function findColon(File $phpcsFile, int $case): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$case]['scope_opener'] ?? null;
        if (!is_int($opener)) {
            return null;
        }

        if ($tokens[$opener]['code'] === T_COLON) {
            return $opener;
        }

        if ($tokens[$opener]['code'] !== T_OPEN_CURLY_BRACKET) {
            return null;
        }

        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), $case, true);

        return $previous !== false && $tokens[$previous]['code'] === T_COLON ? $previous : null;
    }

    /**
     * Report a label that doesn't end in a colon, as the original does. A `;` or `?>` is fixed to a colon.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of the `case` or `default` keyword.
     * @param int $opener The position of the label's scope opener.
     * @param string $type The kind of label, `case` or `default`.
     */
    private function reportMissingColon(File $phpcsFile, int $case, int $opener, string $type): void
    {
        $tokens = $phpcsFile->getTokens();
        $error = '%s statements must be defined using a colon';
        $data = [strtoupper($type)];
        if ($tokens[$opener]['code'] !== T_SEMICOLON && $tokens[$opener]['code'] !== T_CLOSE_TAG) {
            $phpcsFile->addError($error, $case, self::CODE_MISSING_COLON, $data);
            return;
        }

        if (!$phpcsFile->addFixableError($error, $case, self::CODE_MISSING_COLON, $data)) {
            return;
        }

        if ($tokens[$opener]['code'] === T_SEMICOLON) {
            $phpcsFile->fixer->replaceToken($opener, ':');
            return;
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($opener - 1), null, true);
        if ($previous !== false) {
            $phpcsFile->fixer->addContent($previous, ':');
        }
    }

    /**
     * Check the space before a label's colon, and that its body starts on the next line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of the `case` or `default` keyword.
     * @param int $colon The position of the colon.
     * @param int $end The position of the next label, or the switch's `}`.
     * @param string $type The kind of label, `case` or `default`.
     */
    private function checkColonAndBody(File $phpcsFile, int $case, int $colon, int $end, string $type): void
    {
        $tokens = $phpcsFile->getTokens();
        $upperType = strtoupper($type);
        if ($tokens[($colon - 1)]['code'] === T_WHITESPACE) {
            $error = 'There must be no space before the colon in a %s statement';
            if ($phpcsFile->addFixableError($error, $case, "SpaceBeforeColon{$upperType}", [$upperType])) {
                $phpcsFile->fixer->replaceToken(($colon - 1), '');
            }
        }

        for ($next = ($colon + 1); $next < $end; $next++) {
            $code = $tokens[$next]['code'];
            $isCommentOnLaterLine = isset(Tokens::COMMENT_TOKENS[$code])
                && $tokens[$next]['line'] !== $tokens[$colon]['line'];
            if (!isset(Tokens::EMPTY_TOKENS[$code]) || $isCommentOnLaterLine) {
                break;
            }
        }

        if ($tokens[$next]['line'] === ($tokens[$colon]['line'] + 1)) {
            return;
        }

        $error = 'The %s body must start on the line following the statement';
        if (!$phpcsFile->addFixableError($error, $case, "BodyOnNextLine{$upperType}", [$upperType])) {
            return;
        }

        if ($tokens[$next]['line'] === $tokens[$colon]['line']) {
            $padding = str_repeat(' ', ($tokens[$case]['column'] - 1 + $this->indent));
            $phpcsFile->fixer->addContentBefore($next, "{$phpcsFile->eolChar}{$padding}");
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($colon + 1); $i < $next; $i++) {
            // Ignore trailing comments.
            if ($tokens[$i]['line'] === $tokens[$colon]['line']) {
                continue;
            }

            if ($tokens[$i]['line'] === $tokens[$next]['line']) {
                break;
            }

            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check that the statement that ends a case is on a line of its own, and indented as the case's body is.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of the `case` or `default` keyword.
     * @param int $opener The position of the label's scope opener.
     * @param int $end The position of the next label, or the switch's `}`.
     */
    private function checkTerminatorLayout(File $phpcsFile, int $case, int $opener, int $end): void
    {
        $tokens = $phpcsFile->getTokens();
        $terminator = null;
        if ($tokens[$opener]['code'] === T_COLON) {
            // Only once, even if the closer is shared between several labels. PERCS31: a closer that has no
            // scope_condition, after a braced body, crashed the original.
            $closer = $tokens[$case]['scope_closer'] ?? null;
            if (is_int($closer) && ($tokens[$closer]['scope_condition'] ?? null) === $case) {
                $terminator = $closer;
            }
        } else {
            // PERCS31: after a block that's part of the body, the statement that ends the case comes after the block.
            // One that's also an earlier case's closer, after a fall-through, is checked with that case.
            $terminator = $this->findTerminatorAfterBlock($phpcsFile, $opener, $end);
            if ($terminator !== null && isset($tokens[$terminator]['scope_condition'])) {
                $terminator = null;
            }
        }

        if ($terminator === null) {
            return;
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($terminator - 1), $case, true);
        if ($previous !== false && $tokens[$previous]['line'] === $tokens[$terminator]['line']) {
            $error = 'Terminating statement must be on a line by itself';
            if ($phpcsFile->addFixableError($error, $terminator, self::CODE_BREAK_NOT_NEW_LINE)) {
                $phpcsFile->fixer->addNewline($previous);
                $phpcsFile->fixer->replaceToken($terminator, trim((string) $tokens[$terminator]['content']));
            }

            return;
        }

        $diff = ($tokens[$case]['column'] + $this->indent - $tokens[$terminator]['column']);
        if ($diff === 0) {
            return;
        }

        $error = 'Terminating statement must be indented to the same level as the CASE body';
        if (!$phpcsFile->addFixableError($error, $terminator, self::CODE_BREAK_INDENT)) {
            return;
        }

        if ($diff > 0) {
            $phpcsFile->fixer->addContentBefore($terminator, str_repeat(' ', $diff));
        } else {
            $phpcsFile->fixer->substrToken(($terminator - 1), 0, $diff);
        }
    }

    /**
     * PERCS31: find the statement that ends a case whose body starts with a block, after the block.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the block's `{`.
     * @param int $end The position of the next label, or the switch's `}`.
     * @return int|null The position of the terminating statement's first token, or null if there's none after the
     *     block.
     */
    private function findTerminatorAfterBlock(File $phpcsFile, int $opener, int $end): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $blockCloser = $tokens[$opener]['scope_closer'] ?? null;
        if (!is_int($blockCloser)) {
            return null;
        }

        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($end - 1), $blockCloser, true);
        if ($last === false || $tokens[$last]['code'] !== T_SEMICOLON) {
            return null;
        }

        $start = $this->findStatementStart($phpcsFile, $last);

        return $start > $blockCloser && in_array($tokens[$start]['code'], self::TERMINATORS, true) ? $start : null;
    }

    /**
     * PERCS31: find the first token of the statement a semicolon ends.
     *
     * When a case falls through to the next one, the tokenizer makes the next one's `break`, or similar, the closer of
     * both. findStartOfStatement() then jumps from that `break` back to the first case's keyword. Here the `break`
     * itself is returned, so it's still seen as ending the case it's in.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $semicolon The position of the semicolon.
     * @return int The position of the statement's first token.
     */
    private function findStatementStart(File $phpcsFile, int $semicolon): int
    {
        $tokens = $phpcsFile->getTokens();
        $start = $phpcsFile->findStartOfStatement(($semicolon - 1));
        $closer = $tokens[$start]['scope_closer'] ?? null;
        $isCase = $tokens[$start]['code'] === T_CASE || $tokens[$start]['code'] === T_DEFAULT;

        return $isCase && is_int($closer) && $closer > $start && $closer < $semicolon ? $closer : $start;
    }

    /**
     * Check whether a block straight after a label wraps the whole of the case's body: the next thing after its `}`,
     * apart from white space and comments, is another label or the switch's `}`. A block followed by anything else,
     * even a `break`, is a block inside the body.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the block's `{`.
     * @return bool True if the block is the whole body.
     */
    private function wrapsWholeBody(File $phpcsFile, int $opener): bool
    {
        $tokens = $phpcsFile->getTokens();

        // PHP_CodeSniffer gives a case's braces a scope, but not a bracket_closer.
        $closer = $tokens[$opener]['scope_closer'] ?? null;
        if (!is_int($closer)) {
            return true;
        }

        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closer + 1), null, true);

        return $next === false || in_array($tokens[$next]['code'], [T_CASE, T_DEFAULT, T_CLOSE_CURLY_BRACKET], true);
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
            $conditions = $tokens[$i]['conditions'] ?? [];
            if ($isCase && is_array($conditions) && array_key_last($conditions) === $switch) {
                $cases[] = $i;
            }
        }

        return $cases;
    }

    /**
     * Report code on the line of a `case` or `default` label, after its colon. A comment there is allowed.
     *
     * The BodyOnNextLine codes report this, and a blank line after the label too, which section 5.2 doesn't forbid. So
     * the PERCS31 ruleset excludes them, and this check has its own code.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of the `case` or `default` keyword.
     * @param int $colon The position of the label's colon.
     */
    private function checkStatementOnLabelLine(File $phpcsFile, int $case, int $colon): void
    {
        $tokens = $phpcsFile->getTokens();
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($colon + 1), null, true);
        if ($next === false || $tokens[$next]['line'] !== $tokens[$colon]['line']) {
            return;
        }

        $error = 'The %s body must start on the line following the statement';
        $data = [strtoupper((string) $tokens[$case]['content'])];
        if ($phpcsFile->addFixableError($error, $case, self::CODE_STATEMENT_ON_LABEL_LINE, $data)) {
            $padding = str_repeat(' ', ($tokens[$case]['column'] - 1 + $this->indent));
            $phpcsFile->fixer->addContentBefore($next, "{$phpcsFile->eolChar}{$padding}");
        }
    }

    /**
     * Check that a non-empty case followed by another label ends with a terminating statement, or has a comment saying
     * it falls through.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of the `case` or `default` keyword.
     * @param int $colon The position of its colon.
     * @param int $next The position of the next label.
     */
    private function checkFallThrough(File $phpcsFile, int $case, int $colon, int $next): void
    {
        $tokens = $phpcsFile->getTokens();
        $last = $phpcsFile->findPrevious(T_WHITESPACE, ($next - 1), $case, true);
        if ($last === false || $last === $colon || isset(Tokens::COMMENT_TOKENS[$tokens[$last]['code']])) {
            return;
        }

        if ($this->endsWithTerminator($phpcsFile, $case, $colon, $next)) {
            return;
        }

        $phpcsFile->addError(
            'There must be a comment when fall-through is intentional in a non-empty case body',
            $case,
            self::CODE_TERMINATING_COMMENT,
        );
    }

    /**
     * Check whether a case's body ends with a terminating statement.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of the `case` or `default`.
     * @param int $colon The position of the case's colon.
     * @param int $end The position to stop looking at.
     * @return bool True if it does.
     */
    private function endsWithTerminator(File $phpcsFile, int $case, int $colon, int $end): bool
    {
        $tokens = $phpcsFile->getTokens();

        // The tokenizer ends a case's scope at a `break`, `return` or similar at its own level.
        $caseCloser = $tokens[$case]['scope_closer'] ?? null;
        if (
            is_int($caseCloser) && $caseCloser < $end && in_array($tokens[$caseCloser]['code'], self::TERMINATORS, true)
        ) {
            return true;
        }

        return $this->findNestedTerminator($phpcsFile, ($colon + 1), $end) !== false;
    }

    /**
     * Check that the last case, if not empty, ends with a terminating statement.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of the last `case` or `default`.
     * @param int $colon The position of its colon.
     * @param int $closer The position of the switch's `}`.
     */
    private function checkLastCase(File $phpcsFile, int $case, int $colon, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        if ($phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($colon + 1), $closer, true) === false) {
            return;
        }

        if ($this->endsWithTerminator($phpcsFile, $case, $colon, $closer)) {
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
        $phpcsFile->fixer->addContentBefore($lineStart, "{$indent}break;{$phpcsFile->eolChar}");
    }

    /**
     * Check that a multi-line case condition is wrapped in parentheses, with its first condition on the next line, its
     * lines indented, and `):` on a line of their own, level with the `case`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $case The position of `case`.
     * @param int $colon The position of its colon.
     */
    private function checkMultiLineCondition(File $phpcsFile, int $case, int $colon): void
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$colon]['line'] === $tokens[$case]['line']) {
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
            $code = self::CODE_MULTI_LINE_CONDITION_PARENTHESES;
            if (!FixerHelper::addFixableErrorIf($phpcsFile, !$hasTrailingComment, $error, $case, $code)) {
                return;
            }

            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->addContentBefore($first, '(');
            $phpcsFile->fixer->addContent($last, "{$phpcsFile->eolChar}{$indent})");
            $phpcsFile->fixer->endChangeset();
            return;
        }

        $this->checkFirstConditionLine($phpcsFile, $first, $last, $indent);
        $this->checkConditionIndent($phpcsFile, $case, $first, $last);

        $beforeCloser = $phpcsFile->findPrevious(T_WHITESPACE, ($last - 1), $case, true);
        if ($beforeCloser === false || $tokens[$beforeCloser]['line'] !== $tokens[$last]['line']) {
            // On a line of its own, the closing parenthesis lines up with the `case`, as section 5.2's example shows.
            // Too little is left to PERCS31.WhiteSpace.ScopeIndent, which reports it, so it isn't reported twice.
            $found = $tokens[$last]['column'] - 1;
            if ($beforeCloser !== false && $found > strlen($indent)) {
                FixerHelper::reportIndent(
                    $phpcsFile,
                    $last,
                    strlen($indent),
                    'The closing parenthesis of a multi-line case condition must line up with the case; expected %s '
                        . 'spaces, found %s',
                    self::CODE_MULTI_LINE_CONDITION_CLOSER_INDENT,
                    [strlen($indent), $found],
                );
            }

            return;
        }

        $error = 'The closing parenthesis and colon of a multi-line case condition must be on a line of their own';
        $isFixable = $tokens[$beforeCloser]['code'] !== T_COMMENT && !$hasTrailingComment;
        $code = self::CODE_MULTI_LINE_CONDITION_CLOSER;
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $last, $code)) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        FixerHelper::blankBetween($phpcsFile, $beforeCloser, $last);

        $phpcsFile->fixer->addContentBefore($last, "{$phpcsFile->eolChar}{$indent}");
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
        $isFixable = $next === $condition;
        $code = self::CODE_MULTI_LINE_CONDITION_FIRST_LINE;
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $condition, $code)) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        FixerHelper::blankBetween($phpcsFile, $opener, $condition);

        $phpcsFile->fixer->addContent($opener, "{$phpcsFile->eolChar}{$indent}" . str_repeat(' ', $this->indent));
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
        $minimum = ($tokens[$case]['column'] - 1 + $this->indent);
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

    /**
     * Find the next `case` or `default` from a point in the file, skipping nested switches.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position to start looking at.
     * @param int $end The position to stop looking at.
     * @return int|false The position, or false if there's none.
     */
    private function findNextCase(File $phpcsFile, int $stackPtr, int $end): int|false
    {
        $tokens = $phpcsFile->getTokens();
        $found = $phpcsFile->findNext([T_CASE, T_DEFAULT, T_SWITCH], $stackPtr, $end);
        while ($found !== false) {
            // Skip nested switch statements; they are handled on their own.
            $switchCloser = $tokens[$found]['scope_closer'] ?? null;
            if ($tokens[$found]['code'] !== T_SWITCH || !is_int($switchCloser)) {
                break;
            }

            $found = $phpcsFile->findNext([T_CASE, T_DEFAULT, T_SWITCH], $switchCloser, $end);
        }

        return $found;
    }

    /**
     * Find the terminating statement a body ends with, following if/else, try/catch/finally and nested switch blocks.
     * Every branch must end with one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position to start looking at.
     * @param int $end The position to stop looking at.
     * @return int|bool The position of the terminating statement, true if every branch has one, or false if not.
     */
    private function findNestedTerminator(File $phpcsFile, int $stackPtr, int $end): int|bool
    {
        $tokens = $phpcsFile->getTokens();

        $lastToken = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($end - 1), $stackPtr, true);
        if ($lastToken === false) {
            return false;
        }

        if ($tokens[$lastToken]['code'] === T_SEMICOLON) {
            // The last statement of the case: check whether it's a terminating one.
            $terminator = $this->findStatementStart($phpcsFile, $lastToken);

            return in_array($tokens[$terminator]['code'], self::TERMINATORS, true) ? $terminator : false;
        }

        if ($tokens[$lastToken]['code'] !== T_CLOSE_CURLY_BRACKET) {
            return false;
        }

        // A closing brace: if its block belongs to a switch, if, elseif, else, try, catch or finally, look for a
        // terminating statement in that block. Every block of an if/else or switch must have one. For a try/catch, the
        // finally block must have one, or every try and catch block must.
        $currentCloser = $lastToken;
        $hasElseBlock = false;
        $hasCatchWithoutTerminator = false;
        do {
            $scopeOpener = $tokens[$currentCloser]['scope_opener'] ?? null;
            $scopeCloser = $tokens[$currentCloser]['scope_closer'] ?? null;
            if (!is_int($scopeOpener) || !is_int($scopeCloser)) {
                return false;
            }

            $prevToken = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($scopeOpener - 1), $stackPtr, true);
            if ($prevToken === false) {
                return false;
            }

            // Switch, if, elseif and catch clauses have a condition to step over.
            if ($tokens[$prevToken]['code'] === T_CLOSE_PARENTHESIS) {
                $owner = $tokens[$prevToken]['parenthesis_owner'] ?? null;
                if (!is_int($owner)) {
                    return false;
                }

                $prevToken = $owner;
            }

            $code = $tokens[$prevToken]['code'];
            if ($code === T_IF) {
                // Without an else, the whole statement may not terminate.
                if (!$hasElseBlock) {
                    return false;
                }

                return $this->findNestedTerminator($phpcsFile, ($scopeOpener + 1), $scopeCloser);
            }

            if ($code === T_ELSEIF || $code === T_ELSE) {
                // With a terminating statement in this block, carry on with the previous elseif or if.
                if ($this->findNestedTerminator($phpcsFile, ($scopeOpener + 1), $scopeCloser) === false) {
                    return false;
                }

                $currentCloser = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($prevToken - 1), $stackPtr, true);
                $hasElseBlock = $hasElseBlock || $code === T_ELSE;
            } elseif ($code === T_FINALLY) {
                // A terminating statement in the finally block covers the whole try/catch/finally.
                $hasTerminator = $this->findNestedTerminator($phpcsFile, ($scopeOpener + 1), $scopeCloser);
                if ($hasTerminator !== false) {
                    return $hasTerminator;
                }

                // Otherwise, carry on with the previous try or catch.
                $currentCloser = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($prevToken - 1), $stackPtr, true);
            } elseif ($code === T_TRY) {
                // A catch without one, and no finally with one, means the try doesn't need checking.
                if ($hasCatchWithoutTerminator) {
                    return false;
                }

                return $this->findNestedTerminator($phpcsFile, ($scopeOpener + 1), $scopeCloser);
            } elseif ($code === T_CATCH) {
                // Note a catch without one, but carry on, as a finally before it may still have one.
                if ($this->findNestedTerminator($phpcsFile, ($scopeOpener + 1), $scopeCloser) === false) {
                    $hasCatchWithoutTerminator = true;
                }

                $currentCloser = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($prevToken - 1), $stackPtr, true);
            } elseif ($code === T_SWITCH) {
                return $this->findSwitchTerminator($phpcsFile, $prevToken);
            } else {
                return false;
            }
        } while ($currentCloser !== false && $tokens[$currentCloser]['code'] === T_CLOSE_CURLY_BRACKET);

        return true;
    }

    /**
     * Check that every case of a nested switch, and its default, ends with a terminating statement.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $switch The position of the nested `switch`.
     * @return int|bool The last terminating statement found, or false if a case has none, or there's no default.
     */
    private function findSwitchTerminator(File $phpcsFile, int $switch): int|bool
    {
        $tokens = $phpcsFile->getTokens();
        $endOfSwitch = $tokens[$switch]['scope_closer'] ?? null;
        if (!is_int($endOfSwitch)) {
            return false;
        }

        $hasDefaultBlock = false;
        $hasTerminator = false;
        $nextCase = $this->findNextCase($phpcsFile, ($switch + 1), $endOfSwitch);
        for (; $nextCase !== false; $nextCase = $this->findNextCase($phpcsFile, ($nextCase + 1), $endOfSwitch)) {
            $hasDefaultBlock = $hasDefaultBlock || $tokens[$nextCase]['code'] === T_DEFAULT;
            $opener = $tokens[$nextCase]['scope_opener'] ?? null;
            if (!is_int($opener)) {
                return false;
            }

            // A case with no body is skipped.
            $nextCode = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($opener + 1), $endOfSwitch, true);
            if ($nextCode !== false && in_array($tokens[$nextCode]['code'], [T_CASE, T_DEFAULT], true)) {
                continue;
            }

            $endOfCase = $this->findNextCase($phpcsFile, ($opener + 1), $endOfSwitch);
            $hasTerminator = $this->findNestedTerminator($phpcsFile, ($opener + 1), $endOfCase ?: $endOfSwitch);
            if ($hasTerminator === false) {
                return false;
            }
        }

        // Without a default, the whole statement may not terminate.
        return $hasDefaultBlock ? $hasTerminator : false;
    }
}
