<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\PHP\TypeCastSniff as SlevomatSniff;

/**
 * Requires the canonical form of a type in a cast, including `(string)` in place of `(binary)`.
 *
 * PER Coding Style 3.1, section 2.5, says the short form of a type keyword MUST be used, "i.e. `bool` instead of
 * `boolean`, `int` instead of `integer` etc." PHP 8.5 deprecates `(boolean)`, `(integer)`, `(double)` and `(binary)`
 * alike, as non-canonical casts, so all four are reported.
 *
 * This extends SlevomatCodingStandard.PHP.TypeCast, and changes one thing: a `(binary)` cast. The parent says it has
 * no effect, and its fix deletes it. But it's an alias of `(string)`, so `(binary) 5` is `'5'`, and deleting it can
 * change what the code does. This sniff reports it with the InvalidCastUsed code, as the other three are, and fixes it
 * to `(string)`. A sniff's fix can't be changed from a ruleset, so it takes a subclass.
 */
class TypeCastSniff extends SlevomatSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $pointer The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $pointer): void
    {
        // A binary string's prefix, as in b'abc', has the same token as the cast, so go by the content.
        $cast = $phpcsFile->getTokens()[$pointer]['content'];
        assert(is_string($cast));
        if (preg_match('/^\(\s*binary\s*\)$/i', $cast) !== 1) {
            parent::process($phpcsFile, $pointer);
            return;
        }

        $error = 'Cast "%s" is forbidden, use "(string)" instead.';
        if ($phpcsFile->addFixableError($error, $pointer, self::CODE_INVALID_CAST_USED, [$cast])) {
            $phpcsFile->fixer->replaceToken($pointer, '(string)');
        }
    }
}
