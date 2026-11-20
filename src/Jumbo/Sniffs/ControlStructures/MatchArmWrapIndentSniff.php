<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\ControlStructures;

use PERCS31\Helpers\MatchHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a `match` arm to be on one line up to its value, if it fits, and otherwise sets where its `=>` goes and how
 * its lines are indented.
 *
 * PER Coding Style 3.1 doesn't say how a wrapped arm is laid out, so this is an extra rule, to keep each arm distinct.
 *
 * If the arm's conditions, `=>` and the first line of its value fit within maxLineLength, they must be on one line. A
 * comment among them is left to a person.
 *
 * Otherwise:
 * - A line starting with the `=>`, or with the value after the `=>`, is indented one level past the arm's first line.
 * - If the conditions are split over lines, one per line as PERCS31 requires, each is level with the first. The `=>`
 *   then starts the next line, indented once, so adding a condition changes one line:
 *
 *       $first,
 *       $second,
 *           => 'value',
 *
 * A line starting with an operator is left to PERCS31.Operators.OperatorPlacement, and the lines inside brackets to the
 * sniffs for those.
 */
class MatchArmWrapIndentSniff implements Sniff
{
    public const string CODE_INDENT = 'Indent';

    public const string CODE_SHOULD_BE_SINGLE_LINE = 'ShouldBeSingleLine';

    public const string CODE_CONDITION_INDENT = 'ConditionIndent';

    public const string CODE_ARROW_NOT_ON_OWN_LINE = 'ArrowNotOnOwnLine';

    /**
     * The number of spaces in one indent.
     */
    public int $indent = 4;

    /**
     * The longest a line can be, to bring a wrapped arm onto one line.
     */
    public int $maxLineLength = 120;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_MATCH];
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
        $opener = $tokens[$stackPtr]['scope_opener'] ?? null;
        $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
        if (!is_int($opener) || !is_int($closer)) {
            return;
        }

        foreach (MatchHelper::findArms($phpcsFile, $opener, $closer) as $arm) {
            $value = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($arm['arrow'] + 1), $closer, true);
            if ($value === false) {
                continue;
            }

            if (!$this->checkFitsOnOneLine($phpcsFile, $arm['start'], $value)) {
                $this->checkLines($phpcsFile, $arm, $value);
            }
        }
    }

    /**
     * Report an arm that's split before its value, but would fit on one line, and fix it by joining the lines.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $armStart The position of the arm's first token.
     * @param int $value The position of the first token of the arm's value.
     * @return bool True if it was reported.
     */
    private function checkFitsOnOneLine(File $phpcsFile, int $armStart, int $value): bool
    {
        $tokens = $phpcsFile->getTokens();
        if (
            $tokens[$armStart]['line'] === $tokens[$value]['line']
            || $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($armStart + 1), $value) !== false
        ) {
            return false;
        }

        // The arm's start to its value, with each line break as one space, then the rest of the value's first line.
        $joined = $tokens[$armStart]['column'] - 1;
        for ($i = $armStart; $i < $value; $i++) {
            $isBreak = $tokens[$i]['code'] === T_WHITESPACE && str_contains($tokens[$i]['content'], "\n");
            $isIndent = $tokens[$i]['code'] === T_WHITESPACE && $tokens[$i]['column'] === 1;
            if ($isIndent) {
                continue;
            }

            $joined += $isBreak ? 1 : $tokens[$i]['length'];
        }

        for ($i = $value; isset($tokens[$i]) && $tokens[$i]['line'] === $tokens[$value]['line']; $i++) {
            if ($tokens[$i]['content'] !== "\n") {
                $joined += $tokens[$i]['length'];
            }
        }

        if ($joined > $this->maxLineLength) {
            return false;
        }

        $fix = $phpcsFile->addFixableError(
            'A match arm that fits within %s characters must be on one line up to its value',
            $armStart,
            self::CODE_SHOULD_BE_SINGLE_LINE,
            [$this->maxLineLength],
        );
        if (!$fix) {
            return true;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($armStart + 1); $i < $value; $i++) {
            if ($tokens[$i]['code'] !== T_WHITESPACE) {
                continue;
            }

            $phpcsFile->fixer->replaceToken($i, '');
            if ($tokens[($i - 1)]['code'] !== T_WHITESPACE) {
                $phpcsFile->fixer->addContent(($i - 1), ' ');
            }
        }

        $phpcsFile->fixer->endChangeset();

        return true;
    }

    /**
     * Check a wrapped arm's lines: the `=>` and the value indented once, and, if the conditions are split over lines,
     * each condition level with the first and the `=>` on a line of its own.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array{start: int, arrow: int, end: int|null} $arm The arm, as MatchHelper::findArms() returns it.
     * @param int $value The position of the first token of the arm's value.
     */
    private function checkLines(File $phpcsFile, array $arm, int $value): void
    {
        if (MatchHelper::hasSplitConditions($phpcsFile, $arm)) {
            foreach (MatchHelper::findConditions($phpcsFile, $arm) as $condition) {
                $this->checkLine($phpcsFile, $condition, $arm['start'], 0);
            }

            $this->checkArrowStartsLine($phpcsFile, $arm);
        }

        $this->checkLine($phpcsFile, $arm['arrow'], $arm['start'], $this->indent);
        $this->checkLine($phpcsFile, $value, $arm['start'], $this->indent);
    }

    /**
     * Report a `=>` that shares a line with the last of an arm's split conditions, and fix it by moving it to the next
     * line, indented once.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array{start: int, arrow: int, end: int|null} $arm The arm, as MatchHelper::findArms() returns it.
     */
    private function checkArrowStartsLine(File $phpcsFile, array $arm): void
    {
        $arrow = $arm['arrow'];
        $before = $phpcsFile->findPrevious(T_WHITESPACE, ($arrow - 1), $arm['start'], true);
        if ($before === false || $phpcsFile->findFirstOnLine(T_WHITESPACE, $arrow, true) === $arrow) {
            return;
        }

        $error = 'The "=>" of a match arm whose conditions are split over lines must start the next line';
        if (!$phpcsFile->addFixableError($error, $arrow, self::CODE_ARROW_NOT_ON_OWN_LINE)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $firstOfArm = $phpcsFile->findFirstOnLine(T_WHITESPACE, $arm['start'], true);
        assert(is_int($firstOfArm));
        $indent = str_repeat(' ', (($tokens[$firstOfArm]['column'] - 1) + $this->indent));

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($before + 1); $i < $arrow; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContentBefore($arrow, "{$phpcsFile->eolChar}{$indent}");
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check the indent of a token that starts a line, against the line its arm starts on.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token.
     * @param int $armStart The position of the arm's first token.
     * @param int $extra The spaces it's indented past the arm's first line: one indent, or none for a condition.
     */
    private function checkLine(File $phpcsFile, int $stackPtr, int $armStart, int $extra): void
    {
        $tokens = $phpcsFile->getTokens();
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
        if ($firstOnLine !== $stackPtr || $tokens[$stackPtr]['line'] === $tokens[$armStart]['line']) {
            return;
        }

        $firstOfArm = $phpcsFile->findFirstOnLine(T_WHITESPACE, $armStart, true);
        assert(is_int($firstOfArm));
        $expected = $tokens[$firstOfArm]['column'] - 1 + $extra;
        $found = $tokens[$stackPtr]['column'] - 1;
        if ($found === $expected) {
            return;
        }

        $error = $extra === 0
            ? 'A condition of a match arm must be level with the arm\'s first; expected %s spaces, found %s'
            : 'A wrapped match arm must be indented once past its first line; expected %s spaces, found %s';
        $fix = $phpcsFile->addFixableError(
            $error,
            $stackPtr,
            $extra === 0 ? self::CODE_CONDITION_INDENT : self::CODE_INDENT,
            [$expected, $found],
        );
        if (!$fix) {
            return;
        }

        if ($found === 0) {
            $phpcsFile->fixer->addContentBefore($stackPtr, str_repeat(' ', $expected));
        } else {
            $phpcsFile->fixer->replaceToken(($stackPtr - 1), str_repeat(' ', $expected));
        }
    }
}
