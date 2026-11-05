<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\TypeHints;

use PERCS31\Helpers\FileHeaderHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff as SlevomatSniff;

/**
 * Requires `declare(strict_types=1)`, allowing it between PHP tags on the first line of a file with markup.
 *
 * PER Coding Style 3.1, section 3, says that to declare strict types in a file containing markup outside PHP tags,
 * the declaration MUST be on the first line, with its own opening and closing tags, e.g.
 * `<?php declare(strict_types=1) ?>`. SlevomatCodingStandard.TypeHints.DeclareStrictTypes requires blank lines before
 * and after it. This sniff accepts that form, if it declares strict types.
 *
 * Section 3 also says a declare statement MUST NOT contain any spaces inside the parentheses, so this sniff's default
 * is no spaces around the `=`, not the parent's one.
 *
 * The parent's fix adds `strict_types` to an existing declare statement, but in a block `declare`, that's a fatal
 * error. So this sniff reports that case with no fix, and leaves everything else to the parent.
 */
class DeclareStrictTypesSniff extends SlevomatSniff
{
    /**
     * The number of spaces around the `=` in `strict_types=1`.
     */
    public int $spacesCountAroundEqualsSign = 0;

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

        if ($openTagPointer === 0 && $this->hasBlockDeclareWithoutStrictTypes($phpcsFile)) {
            $phpcsFile->addError(
                'Missing declare(strict_types=1), which can\'t be added to a block declare; add it before the block',
                $openTagPointer,
                self::CODE_DECLARE_STRICT_TYPES_MISSING,
            );
            return;
        }

        parent::process($phpcsFile, $openTagPointer);
    }

    /**
     * Check whether the file starts with a block declare statement that doesn't declare strict types.
     *
     * @param File $phpcsFile The file being scanned.
     * @return bool True if it does.
     */
    private function hasBlockDeclareWithoutStrictTypes(File $phpcsFile): bool
    {
        $tokens = $phpcsFile->getTokens();
        $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, 1, null, true);

        return $first !== false
            && $tokens[$first]['code'] === T_DECLARE
            && isset($tokens[$first]['scope_opener'])
            && !$this->declaresStrictTypes($phpcsFile);
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
