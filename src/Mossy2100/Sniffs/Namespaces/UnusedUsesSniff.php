<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Sniffs\Namespaces\UnusedUsesSniff as SlevomatSniff;

/**
 * Reports unused use statements, skipping a file whose `function` or `const` keyword isn't in lower case.
 *
 * SlevomatCodingStandard.Namespaces.UnusedUses reads the type of a use statement with a case-sensitive check, so
 * `use Function Vendor\helper;` is treated as a class import. As no class named `helper` is used, the sniff reports the
 * import as unused, and phpcbf deletes it, which can break the code. The check is in Slevomat's UseStatementHelper,
 * which a subclass can't change.
 *
 * This sniff skips the whole file while such a keyword is there. Universal.UseStatements.LowercaseFunctionConst reports
 * the keyword and fixes it, and on phpcbf's next pass the import is read correctly. Everything else is left to the
 * parent. This can be removed once Slevomat fixes the check.
 */
class UnusedUsesSniff extends SlevomatSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openTagPointer The position of the open tag.
     */
    public function process(File $phpcsFile, int $openTagPointer): void
    {
        if ($this->hasMiscasedKeyword($phpcsFile)) {
            return;
        }

        parent::process($phpcsFile, $openTagPointer);
    }

    /**
     * Check whether a use statement in the file has a `function` or `const` keyword that isn't in lower case.
     *
     * @param File $phpcsFile The file being scanned.
     * @return bool True if a keyword such as `Function` or `CONST` follows `use`.
     */
    private function hasMiscasedKeyword(File $phpcsFile): bool
    {
        $tokens = $phpcsFile->getTokens();
        $usePtr = $phpcsFile->findNext(T_USE, 0);
        for (; $usePtr !== false; $usePtr = $phpcsFile->findNext(T_USE, ($usePtr + 1))) {
            $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($usePtr + 1), null, true);
            if ($next === false || $tokens[$next]['code'] !== T_STRING) {
                continue;
            }

            $content = $tokens[$next]['content'];
            assert(is_string($content));
            $lower = strtolower($content);
            if (($lower === 'function' || $lower === 'const') && $content !== $lower) {
                return true;
            }
        }

        return false;
    }
}
