<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\TypeHints;

use PERCS31\Helpers\FileHeaderHelper;
use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff as SlevomatSniff;

/**
 * Requires `declare(strict_types=1)`, allowing it between PHP tags on the first line of a file with markup.
 *
 * PER Coding Style 3.1, section 3, says that to declare strict types in a file containing markup outside PHP tags,
 * the declaration MUST be on the first line, with its own opening and closing tags, e.g.
 * `<?php declare(strict_types=1) ?>`. SlevomatCodingStandard.TypeHints.DeclareStrictTypes requires blank lines before
 * and after it. This sniff accepts that form, if it declares strict types, and leaves everything else to the parent.
 */
class DeclareStrictTypesSniff extends SlevomatSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openTagPointer The position of the opening tag.
     */
    public function process(File $phpcsFile, int $openTagPointer): void
    {
        if (
            $openTagPointer === 0
            && FileHeaderHelper::hasInlineDeclare($phpcsFile)
            && $this->declaresStrictTypes($phpcsFile)
        ) {
            return;
        }

        parent::process($phpcsFile, $openTagPointer);
    }

    /**
     * Check whether the file's first declare statement sets strict_types to 1.
     *
     * @param File $phpcsFile The file being scanned.
     * @return bool True if it does.
     */
    private function declaresStrictTypes(File $phpcsFile): bool
    {
        $tokens = $phpcsFile->getTokens();
        $declare = $phpcsFile->findNext(T_DECLARE, 0);
        $closer = $declare === false ? null : ($tokens[$declare]['parenthesis_closer'] ?? null);
        if (!is_int($declare) || !is_int($closer)) {
            return false;
        }

        $content = $phpcsFile->getTokensAsString($declare, ($closer - $declare + 1));

        return preg_match('/strict_types\s*=\s*1\b/', $content) === 1;
    }
}
