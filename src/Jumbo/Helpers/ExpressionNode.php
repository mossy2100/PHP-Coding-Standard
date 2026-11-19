<?php

declare(strict_types=1);

namespace Jumbo\Helpers;

/**
 * One part of an expression, as Jumbo.Formatting.ExpressionFormat reads it:
 * - An operand with no binary operator outside its brackets, such as `$a`, `foo($b)` or `-$c`.
 * - A group: an operand in grouping parentheses, such as `($a && $b)` or `!($a || $b)`.
 * - A level: operands joined by the operators of one precedence, such as `$a || $b || $c`. An operand of a level can
 *   itself be a level of operators that bind more tightly, as `$b && $c` is in `$a || $b && $c`.
 */
readonly class ExpressionNode
{
    public const string OPERAND = 'operand';

    public const string GROUP = 'group';

    public const string LEVEL = 'level';

    /**
     * Constructor.
     *
     * @param string $kind OPERAND, GROUP or LEVEL.
     * @param int $start The position of the first token.
     * @param int $end The position of the last token.
     * @param int $flatLength The length of the node written on one line.
     * @param list<ExpressionNode> $children A level's operands, or a group's contents.
     * @param list<int> $operators The positions of a level's operators, one between each pair of operands.
     * @param int|null $opener The position of a group's opening parenthesis.
     * @param int|null $closer The position of a group's closing parenthesis.
     */
    public function __construct(
        public string $kind,
        public int $start,
        public int $end,
        public int $flatLength,
        public array $children = [],
        public array $operators = [],
        public ?int $opener = null,
        public ?int $closer = null,
    ) {}
}
