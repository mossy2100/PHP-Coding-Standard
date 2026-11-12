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
 * the `}` on a line of its own.
 *
 * PER Coding Style 3.1, section 3, shows a group `use` on one line, as in `use Vendor\Package\{ClassA, ClassB};`, and
 * one split across lines with a name on each line. Its text sets no layout, so the examples are read as the two
 * allowed forms, as for a single-line and a multi-line array (section 11). A group that mixes them, as in
 * `use A\{B,` then `C,` then `};`, is reported.
 *
 * PERCS31.WhiteSpace.ConsistentIndent checks each name's indent, and PERCS31.Formatting.TrailingComma the comma after
 * the last name. No sniff in PHP_CodeSniffer, PHPCSExtra, Slevomat or PhpCollective checks which line a name is on.
 */
class GroupUseLayoutSniff implements Sniff
{
    public const string CODE_FIRST_NAME_LINE = 'FirstNameLine';

    public const string CODE_NAME_NOT_ON_OWN_LINE = 'NameNotOnOwnLine';

    public const string CODE_CLOSER_LINE = 'CloserLine';

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
        if ($closer === false || $tokens[$closer]['line'] === $tokens[$stackPtr]['line']) {
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
