<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Files;

use Jumbo\Helpers\FileHeaderHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Files\FileHeaderSniff as Psr12Sniff;

/**
 * Checks the file header, allowing a declare statement between PHP tags on the first line of a file with markup.
 *
 * PER Coding Style 3.1, section 3, says that to declare strict types in a file containing markup outside PHP tags,
 * the declaration MUST be on the first line, with its own opening and closing tags, e.g.
 * `<?php declare(strict_types=1) ?>`. PSR12.Files.FileHeader reads that as a header, and requires blank lines after
 * the tag and the declaration. This sniff skips such a file, as the parent skips other files that mix PHP and markup,
 * and leaves everything else to the parent.
 */
class FileHeaderSniff extends Psr12Sniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return int|null The position to skip to, as the parent returns.
     */
    public function process(File $phpcsFile, int $stackPtr): ?int
    {
        if (FileHeaderHelper::hasInlineDeclare($phpcsFile)) {
            return $phpcsFile->numTokens;
        }

        $result = parent::process($phpcsFile, $stackPtr);

        return is_int($result) ? $result : null;
    }
}
