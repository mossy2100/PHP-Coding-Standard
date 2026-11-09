<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;

/**
 * Helper class for moving between brackets: a bracket's partner, and the bracket a token is inside.
 */
class TokenHelper
{
    /**
     * Find the closer that matches an opening parenthesis, bracket, brace or attribute.
     *
     * Only one of these is set on any token, so the order doesn't matter. A keyword such as `function` has its
     * parameter list's closer, not its body's.
     *
     * @param array<string, mixed> $token The token.
     * @return int|null The closer's position, or null if the token doesn't open anything.
     */
    public static function findCloser(array $token): ?int
    {
        $closer = $token['parenthesis_closer'] ?? $token['bracket_closer'] ?? $token['attribute_closer'] ?? null;

        return is_int($closer) ? $closer : null;
    }

    /**
     * Find the opener that matches a closing parenthesis, bracket, brace or attribute.
     *
     * @param array<string, mixed> $token The token.
     * @return int|null The opener's position, or null if the token doesn't close anything.
     */
    public static function findOpener(array $token): ?int
    {
        $opener = $token['parenthesis_opener'] ?? $token['bracket_opener'] ?? $token['attribute_opener'] ?? null;

        return is_int($opener) ? $opener : null;
    }

    /**
     * Find the innermost bracket, parenthesis or brace that a token is inside, within its statement.
     *
     * Walking back, any complete bracketed group is skipped, such as an earlier element. The walk stops at a `;` or a
     * PHP opening tag, the start of the statement.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the token.
     * @return int|null The position of the opener: `(`, `[` or `{`, or null if the token isn't inside one.
     */
    public static function findContainer(File $phpcsFile, int $ptr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $openers = [T_OPEN_PARENTHESIS, T_OPEN_SQUARE_BRACKET, T_OPEN_SHORT_ARRAY, T_OPEN_CURLY_BRACKET];
        for ($i = ($ptr - 1); $i >= 0; $i--) {
            $code = $tokens[$i]['code'];
            if (in_array($code, $openers, true)) {
                return $i;
            }

            if (in_array($code, [T_SEMICOLON, T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO], true)) {
                return null;
            }

            $opener = self::findOpener($tokens[$i]) ?? $tokens[$i]['scope_opener'] ?? null;
            if (is_int($opener) && $opener < $i) {
                $i = $opener;
            }
        }

        return null;
    }
}
