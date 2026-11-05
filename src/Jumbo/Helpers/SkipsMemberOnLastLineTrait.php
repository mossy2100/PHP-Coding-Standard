<?php

declare(strict_types=1);

namespace Jumbo\Helpers;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\TokenHelper;

/**
 * Stops Slevomat's property, constant and enum case spacing sniffs crashing on a member on the last line of a file.
 *
 * AbstractPropertyConstantAndEnumCaseSpacing assumes there's a line after every member. So `public int $a = 1; }` on a
 * file's last line is a PHP fatal error, not a PHP_CodeSniffer error, and it loses the report for every file in the
 * run. PERCS31 reports that closing brace anyway, and phpcbf fixes it.
 *
 * This can go once Slevomat fixes it. The using sniffs can then go too.
 */
trait SkipsMemberOnLastLineTrait
{
    /**
     * Processes this test, when one of its tokens is encountered, unless the member ends on the file's last line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $pointer The position of the current token in the stack.
     * @return int The position to skip to.
     */
    public function process(File $phpcsFile, int $pointer): int
    {
        // A member ends at its `;`, or the closing brace of a property's hook list.
        $end = $phpcsFile->findNext([T_SEMICOLON, T_OPEN_CURLY_BRACKET], ($pointer + 1));
        $end = $end === false ? false : ($phpcsFile->getTokens()[$end]['bracket_closer'] ?? $end);
        if (!is_int($end) || TokenHelper::findFirstTokenOnNextLine($phpcsFile, $end) === null) {
            return $phpcsFile->numTokens;
        }

        return parent::process($phpcsFile, $pointer);
    }
}
