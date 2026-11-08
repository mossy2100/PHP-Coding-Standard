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
 * error, so this sniff adds a separate statement on the line above. In a file with markup, the fix adds the first-line
 * form at the top instead, as PER requires. A file that starts with a shebang line can't have that form, so if there's
 * markup before its first opening tag, it's reported with no fix, as adding the declare after markup is a fatal error.
 *
 * Section 3 lists "One or more declare statements" as one block, with no blank line in it. The parent always wants a
 * blank line after `strict_types`, so this sniff wants none if another declare statement follows. It leaves everything
 * else to the parent.
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

        // The parent only checks the file's first opening tag.
        $isFirstOpenTag = $phpcsFile->findPrevious(T_OPEN_TAG, ($openTagPointer - 1)) === false;
        if ($isFirstOpenTag && !$this->declaresStrictTypes($phpcsFile)) {
            // A declare with strict_types set to another value is left to the parent, which fixes the value.
            $hasShebang = str_starts_with($phpcsFile->getTokens()[0]['content'], '#!');
            $isAbsent = !$this->hasStrictTypesDirective($phpcsFile);
            if (!$hasShebang && $isAbsent && FileHeaderHelper::hasMarkup($phpcsFile)) {
                $fix = $phpcsFile->addFixableError(
                    'Missing declare(strict_types=1), which in a file with markup goes on the first line, as in '
                        . '<?php declare(strict_types=1) ?>',
                    $openTagPointer,
                    self::CODE_DECLARE_STRICT_TYPES_MISSING,
                );
                if ($fix) {
                    $phpcsFile->fixer->addContentBefore(0, '<?php declare(strict_types=1) ?>' . $phpcsFile->eolChar);
                }

                return;
            }

            if ($this->hasMarkupBefore($phpcsFile, $openTagPointer)) {
                $phpcsFile->addError(
                    'Missing declare(strict_types=1), which can\'t be added here, as it must come before any markup',
                    $openTagPointer,
                    self::CODE_DECLARE_STRICT_TYPES_MISSING,
                );
                return;
            }

            if ($this->startsWithBlockDeclare($phpcsFile, $openTagPointer)) {
                $fix = $phpcsFile->addFixableError(
                    'Missing declare(strict_types=1) before the block declare',
                    $openTagPointer,
                    self::CODE_DECLARE_STRICT_TYPES_MISSING,
                );
                if ($fix) {
                    $block = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($openTagPointer + 1), null, true);
                    assert(is_int($block));
                    $phpcsFile->fixer->addContentBefore($block, 'declare(strict_types=1);' . $phpcsFile->eolChar);
                }

                return;
            }
        }

        $linesCountAfterDeclare = $this->linesCountAfterDeclare;
        if ($isFirstOpenTag && $this->startsWithTwoDeclares($phpcsFile, $openTagPointer)) {
            $this->linesCountAfterDeclare = 0;
        }

        parent::process($phpcsFile, $openTagPointer);
        $this->linesCountAfterDeclare = $linesCountAfterDeclare;
    }

    /**
     * Check whether the first two statements after an opening tag are declare statements.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openTagPointer The position of the opening tag.
     * @return bool True if they are.
     */
    private function startsWithTwoDeclares(File $phpcsFile, int $openTagPointer): bool
    {
        $tokens = $phpcsFile->getTokens();
        $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($openTagPointer + 1), null, true);
        if ($first === false || $tokens[$first]['code'] !== T_DECLARE) {
            return false;
        }

        $end = $tokens[$first]['scope_closer'] ?? $phpcsFile->findNext(T_SEMICOLON, ($first + 1));
        if (!is_int($end)) {
            return false;
        }

        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($end + 1), null, true);

        return $next !== false && $tokens[$next]['code'] === T_DECLARE;
    }

    /**
     * Check whether there's markup before an opening tag. A shebang line is allowed there, and isn't markup.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openTagPointer The position of the opening tag.
     * @return bool True if there is.
     */
    private function hasMarkupBefore(File $phpcsFile, int $openTagPointer): bool
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = 0; $i < $openTagPointer; $i++) {
            if ($tokens[$i]['code'] === T_INLINE_HTML && !($i === 0 && str_starts_with($tokens[$i]['content'], '#!'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the first statement after an opening tag is a block declare statement.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openTagPointer The position of the opening tag.
     * @return bool True if it is.
     */
    private function startsWithBlockDeclare(File $phpcsFile, int $openTagPointer): bool
    {
        $tokens = $phpcsFile->getTokens();
        $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($openTagPointer + 1), null, true);

        return $first !== false && $tokens[$first]['code'] === T_DECLARE && isset($tokens[$first]['scope_opener']);
    }

    /**
     * Check whether the file's first declare statement sets strict_types to 1.
     *
     * @param File $phpcsFile The file being scanned.
     * @return bool True if it does.
     */
    private function declaresStrictTypes(File $phpcsFile): bool
    {
        return FileHeaderHelper::findStrictTypesDeclare($phpcsFile) !== null;
    }

    /**
     * Check whether any declare statement in the file has a strict_types directive, whatever its value.
     *
     * @param File $phpcsFile The file being scanned.
     * @return bool True if one does.
     */
    private function hasStrictTypesDirective(File $phpcsFile): bool
    {
        $tokens = $phpcsFile->getTokens();
        $declare = $phpcsFile->findNext(T_DECLARE, 0);
        while ($declare !== false) {
            $closer = $tokens[$declare]['parenthesis_closer'] ?? null;
            $directives = is_int($closer) ? $phpcsFile->getTokensAsString($declare, ($closer - $declare + 1)) : '';
            if (str_contains($directives, 'strict_types')) {
                return true;
            }

            $declare = $phpcsFile->findNext(T_DECLARE, ($declare + 1));
        }

        return false;
    }
}
