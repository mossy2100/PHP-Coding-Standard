<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Namespaces;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a group `use` split across lines to have one name per line, with the first on the line after the `{`, and
 * the `}` on a line of its own, each name indented once past the `use` line, with no blank line in it, and disallows a
 * space just inside the braces of a group `use` on one line.
 *
 * PER Coding Style 3.1, section 3, shows a group `use` on one line, as in `use Vendor\Package\{ClassA, ClassB};`, and
 * one split across lines with a name on each line. Its text sets no layout, so the examples are read as the two
 * allowed forms, as for a single-line and a multi-line array (section 11). A group that mixes them, as in
 * `use A\{B,` then `C,` then `};`, is reported.
 *
 * Section 3 also says a block of the file header MUST NOT contain a blank line, and a group `use` is part of the block
 * of imports. So a blank line anywhere in one, after the `{`, between two names or before the `}`, is reported as
 * BlankLine.
 *
 * Every single-line example, in section 3 and in the overview, has no space after the `{` or before the `}`, as in
 * `{ClassA as A, ClassB}`. That's read as setting the spacing, as it's how the spec writes every bracketed list.
 *
 * Each name's line is indented once past the `use` line, as section 3's example shows, reported as NameIndent.
 *
 * PERCS31.Formatting.TrailingComma checks the comma after the last name. Universal.WhiteSpace.CommaSpacing checks the
 * commas, and Universal.UseStatements.KeywordSpacing the space around `as`. No sniff in PHP_CodeSniffer, PHPCSExtra,
 * Slevomat or PhpCollective checks which line a name is on, its indent, or the space inside the braces.
 */
class GroupUseLayoutSniff implements Sniff
{
    public const string CODE_FIRST_NAME_LINE = 'FirstNameLine';

    public const string CODE_NAME_NOT_ON_OWN_LINE = 'NameNotOnOwnLine';

    public const string CODE_CLOSER_LINE = 'CloserLine';

    public const string CODE_SPACE_AFTER_OPEN_BRACE = 'SpaceAfterOpenBrace';

    public const string CODE_SPACE_BEFORE_CLOSE_BRACE = 'SpaceBeforeCloseBrace';

    public const string CODE_NAME_INDENT = 'NameIndent';

    public const string CODE_BLANK_LINE = 'BlankLine';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_OPEN_USE_GROUP];
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
        $closer = $phpcsFile->findNext(T_CLOSE_USE_GROUP, ($stackPtr + 1));
        if ($closer === false) {
            return;
        }

        if ($tokens[$closer]['line'] === $tokens[$stackPtr]['line']) {
            $this->checkNoSpace($phpcsFile, ($stackPtr + 1), 'after the opening', self::CODE_SPACE_AFTER_OPEN_BRACE);
            $this->checkNoSpace($phpcsFile, ($closer - 1), 'before the closing', self::CODE_SPACE_BEFORE_CLOSE_BRACE);

            return;
        }

        $indent = LayoutHelper::lineIndent($phpcsFile, $stackPtr);
        $nameIndent = str_repeat(' ', ($indent + LayoutHelper::INDENT));

        // Each name follows the `{` or a comma.
        $first = 'The first name in a group use split across lines must be on the line after the opening brace';
        $other = 'Each name in a group use split across lines must be on a line of its own';
        for ($i = $stackPtr; $i < $closer; $i++) {
            if ($i !== $stackPtr && $tokens[$i]['code'] !== T_COMMA) {
                continue;
            }

            $name = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $closer, true);
            if ($name === false) {
                continue;
            }

            $error = $i === $stackPtr ? $first : $other;
            $code = $i === $stackPtr ? self::CODE_FIRST_NAME_LINE : self::CODE_NAME_NOT_ON_OWN_LINE;
            $this->checkStartsLine($phpcsFile, $i, $name, $nameIndent, $error, $code);
        }

        $last = $phpcsFile->findPrevious(T_WHITESPACE, ($closer - 1), $stackPtr, true);
        if ($last !== false) {
            $error = 'The closing brace of a group use split across lines must be on a line of its own';
            $closerIndent = str_repeat(' ', $indent);
            $this->checkStartsLine($phpcsFile, $last, $closer, $closerIndent, $error, self::CODE_CLOSER_LINE);
        }

        $this->checkNameIndents($phpcsFile, $stackPtr, $closer, strlen($nameIndent));
        $this->checkBlankLines($phpcsFile, $stackPtr, $closer);
    }

    /**
     * Report each blank line in a group use split across lines, and fix it by removing it, as section 3 says a block
     * of the file header MUST NOT contain one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the group's `{`.
     * @param int $closer The position of the group's `}`.
     */
    private function checkBlankLines(File $phpcsFile, int $opener, int $closer): void
    {
        for ($i = ($opener + 1); $i < $closer; $i++) {
            if (!LayoutHelper::isBlankLine($phpcsFile, $i)) {
                continue;
            }

            $error = 'There must be no blank line in a group use';
            if ($phpcsFile->addFixableError($error, $i, self::CODE_BLANK_LINE)) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }
    }

    /**
     * Check that each line of a group use's names is indented once past the `use` line, as section 3's example shows.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the group's `{`.
     * @param int $closer The position of the group's `}`.
     * @param int $expected The number of spaces a name's line is indented by.
     */
    private function checkNameIndents(File $phpcsFile, int $opener, int $closer, int $expected): void
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = ($opener + 1); $i < $closer; $i++) {
            if (isset(Tokens::EMPTY_TOKENS[$tokens[$i]['code']]) || !LayoutHelper::startsLine($phpcsFile, $i)) {
                continue;
            }

            $found = $tokens[$i]['column'] - 1;
            if ($found === $expected) {
                continue;
            }

            $error = 'A name in a group use split across lines must be indented once; expected %s spaces, found %s';
            FixerHelper::reportIndent($phpcsFile, $i, $expected, $error, self::CODE_NAME_INDENT, [$expected, $found]);
        }
    }

    /**
     * Report a space just inside a brace of a group use on one line, and fix it by removing it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $space The position of the token next to the brace.
     * @param string $where Where the space is, for the message.
     * @param string $code The error's code.
     */
    private function checkNoSpace(File $phpcsFile, int $space, string $where, string $code): void
    {
        if ($phpcsFile->getTokens()[$space]['code'] !== T_WHITESPACE) {
            return;
        }

        $error = "There must be no space $where brace of a group use on one line";
        if ($phpcsFile->addFixableError($error, $space, $code)) {
            $phpcsFile->fixer->replaceToken($space, '');
        }
    }

    /**
     * Report a token on the same line as the one before it, and fix it by starting a new line, unless a comment is
     * between them.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $previous The position of the token before.
     * @param int $ptr The position of the token that must start a line.
     * @param string $indent The indent for the new line.
     * @param string $error The message.
     * @param string $code The error's code.
     */
    private function checkStartsLine(
        File $phpcsFile,
        int $previous,
        int $ptr,
        string $indent,
        string $error,
        string $code,
    ): void {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$previous]['line'] !== $tokens[$ptr]['line']) {
            return;
        }

        $isFixable = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($previous + 1), $ptr) === false;
        if (FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $ptr, $code)) {
            FixerHelper::replaceBetween($phpcsFile, $previous, $ptr, $phpcsFile->eolChar . $indent);
        }
    }
}
