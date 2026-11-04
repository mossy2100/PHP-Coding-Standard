<?php

declare(strict_types=1);

namespace Jumbo\Helpers;

/**
 * Helper class for checking the case of names.
 */
class NamingHelper
{
    /**
     * Check whether a name is in PascalCase.
     *
     * @param string $name The name.
     * @return bool True if it starts with a capital, has only letters and digits, and isn't all capitals.
     */
    public static function isPascalCase(string $name): bool
    {
        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) !== 1) {
            return false;
        }

        return strlen($name) === 1 || preg_match('/[a-z]/', $name) === 1;
    }
}
