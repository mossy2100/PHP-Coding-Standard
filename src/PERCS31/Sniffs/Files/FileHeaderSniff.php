<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Files;

use PERCS31\Helpers\FileHeaderHelper;
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
 *
 * Section 3 also lists "One or more declare statements" as one block, with no blank line in it. The parent ends the
 * header at a block declare, so it wants a blank line before one. This sniff counts one as a declare statement, so
 * there's no blank line between it and the declare statements before it.
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

    /**
     * Get the lines of the file's header, including a block declare that follows it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the opening tag.
     * @return array<int, array<string, mixed>> The header's lines.
     */
    public function getHeaderLines(File $phpcsFile, int $stackPtr): array
    {
        $tokens = $phpcsFile->getTokens();
        $lines = parent::getHeaderLines($phpcsFile, $stackPtr);
        $last = end($lines);
        if ($last === false || !in_array($last['type'], ['tag', 'docblock', 'declare'], true)) {
            return $lines;
        }

        $next = $phpcsFile->findNext(T_WHITESPACE, ($last['end'] + 1), null, true);
        if ($next === false || $tokens[$next]['code'] !== T_DECLARE || !isset($tokens[$next]['scope_closer'])) {
            return $lines;
        }

        $lines[] = ['type' => 'declare', 'start' => $next, 'end' => $tokens[$next]['scope_closer']];

        return $lines;
    }
}
