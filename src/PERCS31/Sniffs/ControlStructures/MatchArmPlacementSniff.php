<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\MatchHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires each `match` arm to start on a new line, and each condition of an arm to start a line when the arm's
 * conditions are split over lines.
 *
 * PER Coding Style 3.1, section 5.2, says a `match` expression looks like its example, which has one arm per line. So
 * `1 => 2, default => 3,` on one line is reported, and the fix moves the second arm to the next line. Several
 * conditions in one arm, as in `1, 2, 3 => 'a',`, are one arm, so they're fine.
 *
 * An arm's conditions are a list, and every list the spec splits over lines has one item per line: arguments (4.7),
 * parameters (4.5), an array's values (11) and interfaces (4.1). So if the list doesn't start and end on the same line,
 * each condition starts a line. That includes a list with a condition that spans lines, such as an array.
 * PERCS31.Formatting.TrailingComma requires the comma after the last. The indent is left to
 * PERCS31.WhiteSpace.ScopeIndent.
 */
class MatchArmPlacementSniff implements Sniff
{
    public const string CODE_NOT_ON_OWN_LINE = 'NotOnOwnLine';

    public const string CODE_CONDITION_NOT_ON_OWN_LINE = 'ConditionNotOnOwnLine';

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
            if ($arm['end'] !== null) {
                $this->checkNextArm($phpcsFile, $arm['end'], $closer);
            }

            $this->checkConditions($phpcsFile, $arm);
        }
    }

    /**
     * Check that the arm after a comma, if there's one, starts on a new line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $comma The position of the comma that ends the previous arm.
     * @param int $closer The position of the match's closing brace.
     */
    private function checkNextArm(File $phpcsFile, int $comma, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($comma + 1), $closer, true);
        if ($next === false || $tokens[$next]['line'] !== $tokens[$comma]['line']) {
            return;
        }

        $error = 'Each match arm must start on a new line';
        $isFixable = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($comma + 1), $next) === false;
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $next, self::CODE_NOT_ON_OWN_LINE)) {
            return;
        }

        $indent = str_repeat(' ', LayoutHelper::lineIndent($phpcsFile, $comma));
        FixerHelper::replaceBetween($phpcsFile, $comma, $next, "{$phpcsFile->eolChar}{$indent}");
    }

    /**
     * Check that each condition of an arm starts a line, if the arm's conditions are split over lines.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array{start: int, arrow: int, end: int|null} $arm The arm, as MatchHelper::findArms() returns it.
     */
    private function checkConditions(File $phpcsFile, array $arm): void
    {
        if (!MatchHelper::hasSplitConditions($phpcsFile, $arm)) {
            return;
        }

        $indent = str_repeat(' ', LayoutHelper::lineIndent($phpcsFile, $arm['start']));
        foreach (MatchHelper::findConditions($phpcsFile, $arm) as $condition) {
            $startsLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $condition, true) === $condition;
            if ($condition === $arm['start'] || $startsLine) {
                continue;
            }

            $before = $phpcsFile->findPrevious(T_WHITESPACE, ($condition - 1), $arm['start'], true);
            if ($before === false) {
                continue;
            }

            $error = 'Each condition of a match arm split over lines must start a line';
            if ($phpcsFile->addFixableError($error, $condition, self::CODE_CONDITION_NOT_ON_OWN_LINE)) {
                FixerHelper::replaceBetween($phpcsFile, $before, $condition, "{$phpcsFile->eolChar}{$indent}");
            }
        }
    }
}
