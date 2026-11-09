<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

/**
 * Helper class for moving between a bracket and its partner.
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
}
