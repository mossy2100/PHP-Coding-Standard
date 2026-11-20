<?php

declare(strict_types=1);

namespace Jumbo\Helpers;

/**
 * One indivisible part of a string's text, as Jumbo.Formatting.ExpressionFormat reads it: a character, an escape
 * sequence, or an interpolated variable. A string is only ever split between two atoms.
 */
readonly class StringAtom
{
    public const string TEXT = 'text';

    public const string VARIABLE = 'variable';

    /**
     * Constructor.
     *
     * @param string $kind TEXT or VARIABLE.
     * @param string|null $single How it's written in a single-quoted string, or null if it can't be.
     * @param string $double How it's written in a double-quoted string. For a variable, the form it's written in.
     * @param int $source The position of the string literal it came from, or -1 if it came from anything else.
     * @param bool $simple Whether a variable is written without braces, so braces can be added if they're needed.
     * @param string $operand The code of a variable that came from an operand, as the operand is written.
     */
    public function __construct(
        public string $kind,
        public ?string $single,
        public string $double,
        public int $source = -1,
        public bool $simple = false,
        public string $operand = '',
    ) {}

    /**
     * Check whether it's a space, before which a string's text can be split.
     *
     * @return bool True if it is.
     */
    public function isSpace(): bool
    {
        return $this->kind === self::TEXT && $this->double === ' ';
    }
}
