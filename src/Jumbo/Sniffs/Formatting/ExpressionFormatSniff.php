<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Formatting;

use Jumbo\Helpers\ExpressionHelper;
use Jumbo\Helpers\ExpressionNode;
use Jumbo\Helpers\StringHelper;
use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires an expression to be split across lines only where it must be, at its loosest operators.
 *
 * PER Coding Style 3.1, section 6.4, lets an expression be split before any operator, so this is an extra rule. The
 * sniff works out the one layout an expression should have, and reports it if it's written any other way:
 * - If the expression fits within the line length on one line, it's written on one line.
 * - Otherwise, it's split before the operators that bind most loosely, outside any grouping parentheses, such as the
 *   `||` in `$a && $b || $c`. Each line holds as many operands as fit, so a line only breaks before the operator whose
 *   operand wouldn't fit on it. Each new line is indented once past the line the expression starts on, or lined up
 *   with the first line of a control structure's condition, when that starts its own line.
 * - An operand too long for a line of its own is split the same way, at its own loosest operators. One without
 *   parentheses keeps the same indent, as `$b` then `&& $c` does in `$a` then `|| $b` then `&& $c`. One in grouping
 *   parentheses keeps them with its contents, and its new lines are indented once past the line with its `(`.
 *
 * An expression ends at anything that isn't part of it: a `;`, `,`, `=>`, assignment, ternary `?` or `:`, or the
 * brackets of a call, array or control structure. Each argument of a call is its own expression. An operand isn't
 * changed inside, so the sniff skips an expression with a line break inside an operand, such as a multi-line call or
 * closure, and one with a comment, a heredoc, a nowdoc, a multi-line string or an arrow function. It also skips a
 * `case` condition. It never splits a ternary's condition or value, which share their lines with the rest of the
 * ternary, or an expression written on one line that's too long, unless it's a whole statement, a value after an
 * assignment, `return`, `=>` or the like, or a control structure's condition on its own line, where a split can't
 * clash with the layout of the code around it.
 *
 * It covers every binary operator except the assignment operators and `and`, `or` and `xor`, which bind more loosely
 * than assignment. It only decides where the lines break. An operator at the end of a line counts as a break at it,
 * and PERCS31.Operators.OperatorPlacement reports it there, and checks each line's indent. The fix puts each operator
 * at the start of its line, as that sniff does.
 */
class ExpressionFormatSniff implements Sniff
{
    public const string CODE_SHOULD_BE_SINGLE_LINE = 'ShouldBeSingleLine';

    public const string CODE_INCORRECT_SPLIT = 'IncorrectSplit';

    public const string CODE_INCORRECT_STRING_FORMAT = 'IncorrectStringFormat';

    public const string CODE_UNNECESSARY_STRING_CONVERSION = 'UnnecessaryStringConversion';

    /**
     * Tokens after which an expression on one line that's too long may be split.
     *
     * @var list<int|string>
     */
    private const array SPLITTABLE_AFTER = [
        T_SEMICOLON,
        T_OPEN_CURLY_BRACKET,
        T_CLOSE_CURLY_BRACKET,
        T_OPEN_TAG,
        T_RETURN,
        T_ECHO,
        T_PRINT,
        T_THROW,
        T_YIELD,
        T_YIELD_FROM,
        T_DOUBLE_ARROW,
        T_MATCH_ARROW,
        T_FN_ARROW,
    ];

    /**
     * Control structures whose header is indented as a condition, as PERCS31.Operators.OperatorPlacement does it.
     *
     * @var list<int|string>
     */
    private const array CONDITION_OWNERS = [T_IF, T_ELSEIF, T_WHILE, T_SWITCH, T_MATCH, T_FOR, T_FOREACH];

    /**
     * Operators that join a condition's parts, which line up with each other in a condition.
     *
     * @var list<int|string>
     */
    private const array BOOLEAN_OPERATORS = [T_BOOLEAN_AND, T_BOOLEAN_OR];

    /**
     * Maximum line length.
     */
    public int $maxLineLength = 120;

    /**
     * Whether to rewrite string expressions as the fewest strings they can be written as, and split long strings.
     */
    public bool $reflowStringExpressions = false;

    /**
     * The file whose expressions are in $checked.
     */
    private string $checkedFile = '';

    /**
     * The first token of each expression already checked in the current file, as each of its operators is registered.
     *
     * @var array<int, true>
     */
    private array $checked = [];

    /**
     * The string expressions in the current expression that start a line of their own and don't fit on it, which are
     * laid out as strings, with the code each should be, by the position of each one's first token.
     *
     * @var array<int, array{ExpressionNode, string}>
     */
    private array $delegated = [];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [
            ...array_keys(ExpressionHelper::precedences()),
            T_CONSTANT_ENCAPSED_STRING,
            T_DOUBLE_QUOTED_STRING,
            T_ECHO,
            T_PRINT,
            T_OPEN_TAG_WITH_ECHO,
        ];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $code = $phpcsFile->getTokens()[$stackPtr]['code'];
        if (in_array($code, [T_CONSTANT_ENCAPSED_STRING, T_DOUBLE_QUOTED_STRING], true)) {
            if ($this->reflowStringExpressions) {
                $this->processLiteral($phpcsFile, $stackPtr);
            }

            return;
        }

        if (in_array($code, [T_ECHO, T_PRINT, T_OPEN_TAG_WITH_ECHO], true)) {
            if ($this->reflowStringExpressions) {
                $this->processConversions($phpcsFile, $stackPtr);
            }

            return;
        }

        if (!ExpressionHelper::isBinary($phpcsFile, $stackPtr)) {
            return;
        }

        if ($phpcsFile->getFilename() !== $this->checkedFile) {
            $this->checkedFile = $phpcsFile->getFilename();
            $this->checked = [];
        }

        $region = ExpressionHelper::findExpression($phpcsFile, $stackPtr);
        if ($region === null || isset($this->checked[$region[0]])) {
            return;
        }

        $this->checked[$region[0]] = true;
        $expression = ExpressionHelper::read($phpcsFile, $stackPtr);
        if ($expression === null) {
            return;
        }

        [$root, $before, $after] = $expression;
        $start = $root->start;
        $end = $root->end;
        $tokens = $phpcsFile->getTokens();
        $column = $tokens[$start]['column'] - 1;
        $lineIndent = LayoutHelper::lineIndent($phpcsFile, $start);
        $conditionOpener = $this->findConditionOpener($phpcsFile, $start);
        $isCondition = $conditionOpener !== null && LayoutHelper::startsLine($phpcsFile, $start);
        $continuationIndent = $lineIndent + LayoutHelper::INDENT;
        if ($conditionOpener !== null) {
            $continuationIndent = LayoutHelper::lineIndent($phpcsFile, $conditionOpener) + LayoutHelper::INDENT;
        }

        if ($this->reflowStringExpressions && $this->processStrings($phpcsFile, $root, $before, $after)) {
            return;
        }

        $breaks = [];
        $trailing = $this->trailingLength($phpcsFile, $end);
        $inCondition = $conditionOpener !== null;
        $this->delegated = [];
        $this->layout($phpcsFile, $root, $column, $lineIndent, $continuationIndent, $trailing, $inCondition, $breaks);
        if ($this->reflowStringExpressions) {
            $this->processNestedStrings($phpcsFile, $root);
            foreach ($this->delegated as [$node, $code]) {
                $this->reportString($phpcsFile, $node->start, $node->end, $code);
            }
        }

        $current = [];
        $misplaced = false;
        $this->findCurrentBreaks($phpcsFile, $root, $current, $misplaced);
        ksort($current);
        ksort($breaks);
        if (!$misplaced && array_keys($current) === array_keys($breaks)) {
            return;
        }

        // A ternary's condition or value shares its lines with the rest of the ternary, so it's never split.
        if ($breaks !== [] && $this->isInTernary($phpcsFile, $before, $after)) {
            return;
        }

        // An expression on one line is only split where that can't clash with the layout around it.
        if ($current === [] && !$misplaced && !$isCondition && !$this->isSplittableAfter($phpcsFile, $before)) {
            return;
        }

        $error = 'An expression too long for %s characters must be split only before its loosest operators, with as '
            . 'many operands on each line as fit.';
        $code = self::CODE_INCORRECT_SPLIT;
        if ($breaks === []) {
            $error = 'An expression split across lines fits within %s characters, so it must be written on one line.';
            $code = self::CODE_SHOULD_BE_SINGLE_LINE;
        }

        if ($phpcsFile->addFixableError($error, $start, $code, [$this->maxLineLength])) {
            $phpcsFile->fixer->beginChangeset();
            $this->applyLayout($phpcsFile, $root, $breaks);
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * Check whether an expression is a ternary's condition or value.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $before The position of the token that ends the expression before it, or -1.
     * @param int $after The position of the token that ends it after it.
     * @return bool True if it is.
     */
    private function isInTernary(File $phpcsFile, int $before, int $after): bool
    {
        $tokens = $phpcsFile->getTokens();
        $ternary = [T_INLINE_THEN, T_INLINE_ELSE];

        return in_array($tokens[$after]['code'] ?? null, $ternary, true)
            || ($before >= 0 && in_array($tokens[$before]['code'], $ternary, true));
    }

    /**
     * Check whether an expression on one line can be split, from the token before it: it's a whole statement, or a
     * value after an assignment, `return`, `=>` or the like.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $before The position of the token that ends the expression before it, or -1.
     * @return bool True if it can.
     */
    private function isSplittableAfter(File $phpcsFile, int $before): bool
    {
        $tokens = $phpcsFile->getTokens();

        return $before < 0 || in_array($tokens[$before]['code'], self::SPLITTABLE_AFTER, true)
            || isset(Tokens::ASSIGNMENT_TOKENS[$tokens[$before]['code']]);
    }

    /**
     * Check whether a node is a chain of `.` operations.
     *
     * @param File $phpcsFile The file being scanned.
     * @param ExpressionNode $node The node.
     * @return bool True if it is.
     */
    private function isConcatenation(File $phpcsFile, ExpressionNode $node): bool
    {
        return $node->kind === ExpressionNode::LEVEL
            && $phpcsFile->getTokens()[$node->operators[0]]['code'] === T_STRING_CONCAT;
    }

    /**
     * Check whether a node is one string literal, or one in grouping parentheses.
     *
     * @param File $phpcsFile The file being scanned.
     * @param ExpressionNode $node The node.
     * @return bool True if it is.
     */
    private function isStringLiteral(File $phpcsFile, ExpressionNode $node): bool
    {
        if ($node->kind === ExpressionNode::GROUP && $node->opener === $node->start) {
            return $this->isStringLiteral($phpcsFile, $node->children[0]);
        }

        $code = $phpcsFile->getTokens()[$node->start]['code'];

        return $node->kind === ExpressionNode::OPERAND && $node->start === $node->end
            && in_array($code, [T_CONSTANT_ENCAPSED_STRING, T_DOUBLE_QUOTED_STRING], true);
    }

    /**
     * Check whether a string expression is laid out as a whole, including splitting its strings, rather than only
     * rewritten on one line, leaving its line breaks to the rest of the sniff.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of its first token.
     * @param int $end The position of its last token.
     * @param int $before The position of the token that ends it before it, or -1.
     * @param int $after The position of the token that ends it after it.
     * @return bool True if it is.
     */
    private function isStringLaidOut(File $phpcsFile, int $start, int $end, int $before, int $after): bool
    {
        $tokens = $phpcsFile->getTokens();

        return $this->findConditionOpener($phpcsFile, $start) === null
            && (!$this->isInTernary($phpcsFile, $before, $after)
                || $this->isTernaryBranchOnItsOwnLine($phpcsFile, $before))
            && ($this->isSplittableAfter($phpcsFile, $before) || LayoutHelper::startsLine($phpcsFile, $start)
                || $tokens[$start]['line'] !== $tokens[$end]['line']);
    }

    /**
     * Check the string expressions in an expression: the whole of it, laid out, if it's a chain of `.` operations
     * where it can be, or else each chain of `.` operations in it, on one line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param ExpressionNode $root The expression.
     * @param int $before The position of the token that ends it before it, or -1.
     * @param int $after The position of the token that ends it after it.
     * @return bool True if the whole expression was checked as a string expression, so its layout is checked.
     */
    private function processStrings(File $phpcsFile, ExpressionNode $root, int $before, int $after): bool
    {
        if (
            $this->isConcatenation($phpcsFile, $root)
            && $this->isStringLaidOut($phpcsFile, $root->start, $root->end, $before, $after)
        ) {
            $helper = new StringHelper($phpcsFile);
            if ($helper->read($root)) {
                $this->reportString(
                    $phpcsFile,
                    $root->start,
                    $root->end,
                    $this->layoutString($phpcsFile, $helper, $root->start, $root->end),
                );
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether an expression is a whole ternary branch whose `?` or `:` starts a line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $before The position of the token that ends the expression before it, or -1.
     * @return bool True if it is.
     */
    private function isTernaryBranchOnItsOwnLine(File $phpcsFile, int $before): bool
    {
        $tokens = $phpcsFile->getTokens();

        return $before >= 0 && in_array($tokens[$before]['code'], [T_INLINE_THEN, T_INLINE_ELSE], true)
            && LayoutHelper::startsLine($phpcsFile, $before);
    }

    /**
     * Check each chain of `.` operations in a node, rewritten on one line, leaving its line breaks as they are unless
     * it changes.
     *
     * @param File $phpcsFile The file being scanned.
     * @param ExpressionNode $node The node.
     */
    private function processNestedStrings(File $phpcsFile, ExpressionNode $node): void
    {
        if ($this->isDelegated($node)) {
            return;
        }

        // Grouping parentheses around a string expression that becomes one string aren't needed.
        $inner = $node->children[0] ?? null;
        if (
            $node->kind === ExpressionNode::GROUP && $node->opener === $node->start && $inner !== null
            && ($this->isConcatenation($phpcsFile, $inner) || $this->isStringLiteral($phpcsFile, $inner))
        ) {
            $helper = new StringHelper($phpcsFile);
            if ($helper->read($inner) && count($helper->segments()) === 1) {
                $this->rewriteNestedString($phpcsFile, $node, $helper->flat());
                return;
            }
        }

        if (!$this->isConcatenation($phpcsFile, $node)) {
            foreach ($node->children as $child) {
                $this->processNestedStrings($phpcsFile, $child);
            }

            return;
        }

        $helper = new StringHelper($phpcsFile);
        if ($helper->read($node)) {
            $this->rewriteNestedString($phpcsFile, $node, $helper->flat());
        }
    }

    /**
     * Report a string expression inside a larger expression that isn't written as it should be, on one line, unless
     * that wouldn't fit on its line, as the line breaks it would need aren't checked here.
     *
     * @param File $phpcsFile The file being scanned.
     * @param ExpressionNode $node The node to replace.
     * @param string $flat The code it should be.
     */
    private function rewriteNestedString(File $phpcsFile, ExpressionNode $node, string $flat): void
    {
        $tokens = $phpcsFile->getTokens();
        $current = '';
        for ($i = $node->start; $i <= $node->end; $i++) {
            $current .= $tokens[$i]['code'] === T_WHITESPACE ? ' ' : $tokens[$i]['content'];
        }

        $column = $tokens[$node->start]['column'] - 1;
        $trailing = $this->trailingLength($phpcsFile, $node->end);
        if ($current !== $flat && ($column + mb_strlen($flat) + $trailing) <= $this->maxLineLength) {
            $this->reportString($phpcsFile, $node->start, $node->end, $flat);
        }
    }

    /**
     * Check a string literal that's a whole expression, which is split if it's too long.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the literal.
     */
    private function processLiteral(File $phpcsFile, int $stackPtr): void
    {
        $region = ExpressionHelper::findExpression($phpcsFile, $stackPtr);
        if ($region === null) {
            return;
        }

        // Grouping parentheses around the literal aren't needed.
        [$start, $end, $before, $after] = $region;
        $tokens = $phpcsFile->getTokens();
        $innerStart = $start;
        $innerEnd = $end;
        while (
            $innerStart < $innerEnd && ExpressionHelper::isGrouping($phpcsFile, $innerStart)
            && $tokens[$innerStart]['parenthesis_closer'] === $innerEnd
        ) {
            $innerStart = (int) $phpcsFile->findNext(T_WHITESPACE, ($innerStart + 1), $innerEnd, true);
            $innerEnd = (int) $phpcsFile->findPrevious(T_WHITESPACE, ($innerEnd - 1), $innerStart, true);
        }

        $owner = $before >= 0 ? ($tokens[$before]['parenthesis_owner'] ?? null) : null;
        if (
            $innerStart !== $stackPtr || $innerEnd !== $stackPtr
            || ExpressionHelper::isSkipped($phpcsFile, $start, $end, $before)
            || (is_int($owner) && $tokens[$owner]['code'] === T_DECLARE)
        ) {
            return;
        }

        $node = ExpressionHelper::parse($phpcsFile, $stackPtr, $stackPtr);
        $helper = new StringHelper($phpcsFile);
        if ($node === null || !$helper->read($node)) {
            return;
        }

        if ($this->isStringLaidOut($phpcsFile, $start, $end, $before, $after)) {
            $this->reportString($phpcsFile, $start, $end, $this->layoutString($phpcsFile, $helper, $start, $end));
        } elseif ($start !== $stackPtr) {
            $this->rewriteNestedString(
                $phpcsFile,
                new ExpressionNode(ExpressionNode::GROUP, $start, $end, 0),
                $helper->flat(),
            );
        }
    }

    /**
     * Lay out a string expression from where it starts.
     *
     * @param File $phpcsFile The file being scanned.
     * @param StringHelper $helper The expression, read.
     * @param int $start The position of the first token it replaces.
     * @param int $end The position of the last token it replaces.
     * @return string The code.
     */
    private function layoutString(File $phpcsFile, StringHelper $helper, int $start, int $end): string
    {
        $column = $phpcsFile->getTokens()[$start]['column'] - 1;
        $indent = LayoutHelper::lineIndent($phpcsFile, $start) + LayoutHelper::INDENT;
        $code = $helper->layout($column, $indent, $this->trailingLength($phpcsFile, $end), $this->maxLineLength);

        return str_replace("\n", $phpcsFile->eolChar, $code);
    }

    /**
     * Report a string expression that isn't written as it should be, and fix it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of its first token.
     * @param int $end The position of its last token.
     * @param string $code The code it should be.
     */
    private function reportString(File $phpcsFile, int $start, int $end, string $code): void
    {
        if ($phpcsFile->getTokensAsString($start, ($end - $start + 1)) === $code) {
            return;
        }

        $error = 'A string expression must be written as the fewest strings it can be, with variables interpolated, '
            . 'and split only where it doesn\'t fit within %s characters.';
        if (!$phpcsFile->addFixableError($error, $start, self::CODE_INCORRECT_STRING_FORMAT, [$this->maxLineLength])) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($start, $code);
        for ($i = ($start + 1); $i <= $end; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check each argument of `echo`, `print` or `<?=` for a `(string)` cast or `->__toString()` call, which isn't
     * needed, as they convert their arguments to strings.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the keyword or tag.
     */
    private function processConversions(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $ptr = $stackPtr;
        do {
            $argument = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($ptr + 1), null, true);
            $region = $argument === false ? null : ExpressionHelper::findExpression($phpcsFile, $argument);
            if ($region === null) {
                return;
            }

            [$start, $end, , $after] = $region;
            $node = ExpressionHelper::parse($phpcsFile, $start, $end);
            if ($node !== null && $node->kind !== ExpressionNode::LEVEL) {
                $this->processConversion($phpcsFile, $stackPtr, $start, $end);
            }

            $ptr = $after;
        } while ($tokens[$stackPtr]['code'] !== T_PRINT && ($tokens[$ptr]['code'] ?? null) === T_COMMA);
    }

    /**
     * Report a `(string)` cast or `->__toString()` call around a whole argument of `echo`, `print` or `<?=`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $keyword The position of the keyword or tag.
     * @param int $start The position of the argument's first token.
     * @param int $end The position of its last token.
     */
    private function processConversion(File $phpcsFile, int $keyword, int $start, int $end): void
    {
        $tokens = $phpcsFile->getTokens();
        $from = null;
        $to = null;
        if ($tokens[$start]['code'] === T_STRING_CAST && $start < $end) {
            $from = $start;
            $to = (int) $phpcsFile->findNext(T_WHITESPACE, ($start + 1), null, true) - 1;
        } elseif (
            ($end - 4) >= $start && $tokens[$end]['code'] === T_CLOSE_PARENTHESIS
            && $tokens[($end - 1)]['code'] === T_OPEN_PARENTHESIS && $tokens[($end - 2)]['code'] === T_STRING
            && strtolower($tokens[($end - 2)]['content']) === '__tostring'
            && $tokens[($end - 3)]['code'] === T_OBJECT_OPERATOR
        ) {
            $from = $end - 3;
            $to = $end;
        }

        if ($from === null) {
            return;
        }

        $keywordName = $tokens[$keyword]['code'] === T_OPEN_TAG_WITH_ECHO
            ? '<?='
            : strtolower($tokens[$keyword]['content']);
        $fix = $phpcsFile->addFixableError(
            'The string conversion isn\'t needed, as %s converts its arguments to strings.',
            $from,
            self::CODE_UNNECESSARY_STRING_CONVERSION,
            [$keywordName],
        );
        if ($fix) {
            $phpcsFile->fixer->beginChangeset();
            for ($i = $from; $i <= $to; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }

            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * Find the parentheses of the control structure whose condition, or header, an expression is directly in.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the expression's first token.
     * @return int|null The position of the opening parenthesis, or null if it isn't in one.
     */
    private function findConditionOpener(File $phpcsFile, int $start): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $openers = array_keys($tokens[$start]['nested_parenthesis'] ?? []);
        $opener = end($openers);
        $owner = is_int($opener) ? ($tokens[$opener]['parenthesis_owner'] ?? null) : null;

        return is_int($opener) && is_int($owner) && in_array($tokens[$owner]['code'], self::CONDITION_OWNERS, true)
            ? $opener
            : null;
    }

    /**
     * Get the length of the code after an expression on its last line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $end The position of the expression's last token.
     * @return int The number of characters, without trailing whitespace.
     */
    private function trailingLength(File $phpcsFile, int $end): int
    {
        $tokens = $phpcsFile->getTokens();
        $text = '';
        for ($i = ($end + 1); isset($tokens[$i]); $i++) {
            $content = $tokens[$i]['content'];
            $newline = strpos($content, "\n");
            if ($newline !== false) {
                $text .= substr($content, 0, $newline);
                break;
            }

            $text .= $content;
        }

        return mb_strlen(rtrim($text));
    }

    /**
     * Work out where a node's lines break, splitting it only if it doesn't fit, and as few times as it can.
     *
     * @param File $phpcsFile The file being scanned.
     * @param ExpressionNode $node The node.
     * @param int $column The number of characters before the node on its first line.
     * @param int $lineIndent The indent of the node's first line.
     * @param int $indent The indent for a level's new lines.
     * @param int $trailing The number of characters after the node on its last line.
     * @param bool $inCondition Whether the node is directly in a control structure's condition, outside any group or
     * operator other than `&&` and `||`.
     * @param array<int, int> $breaks The operators to break the line before, with each one's indent.
     * @return int The number of characters before the node's end, on its last line.
     */
    private function layout(
        File $phpcsFile,
        ExpressionNode $node,
        int $column,
        int $lineIndent,
        int $indent,
        int $trailing,
        bool $inCondition,
        array &$breaks,
    ): int {
        $flatEnd = $column + $node->flatLength;
        if ($node->kind === ExpressionNode::OPERAND || ($flatEnd + $trailing) <= $this->maxLineLength) {
            return $flatEnd;
        }

        // A group's contents go on its first line, and any new lines inside it are indented once past the line with
        // its `(`, as PERCS31.Operators.OperatorPlacement requires.
        if ($node->kind === ExpressionNode::GROUP) {
            $inner = $node->children[0];
            $innerColumn = $column + ($node->flatLength - $inner->flatLength - 1);
            $innerIndent = $lineIndent + LayoutHelper::INDENT;

            return $this->layout(
                $phpcsFile,
                $inner,
                $innerColumn,
                $lineIndent,
                $innerIndent,
                ($trailing + 1),
                false,
                $breaks,
            ) + 1;
        }

        // In a condition, the parts joined by `&&` and `||` line up, but any other operator's new lines are indented
        // once past the line its operand starts on, as PERCS31.Operators.OperatorPlacement requires.
        $tokens = $phpcsFile->getTokens();
        if ($inCondition && !in_array($tokens[$node->operators[0]]['code'], self::BOOLEAN_OPERATORS, true)) {
            $indent = $lineIndent + LayoutHelper::INDENT;
            $inCondition = false;
        }

        // Each operand joins the line it follows if it fits, and starts a new line, with its operator, if it doesn't.
        $lastIndex = count($node->operators);
        $end = $this->layout($phpcsFile, $node->children[0], $column, $lineIndent, $indent, 0, $inCondition, $breaks);
        foreach ($node->operators as $index => $operator) {
            $child = $node->children[($index + 1)];
            $childTrailing = ($index + 1) === $lastIndex ? $trailing : 0;
            $operatorLength = mb_strlen($tokens[$operator]['content']);
            $joinedEnd = $end + $operatorLength + 2 + $child->flatLength;
            if (($joinedEnd + $childTrailing) <= $this->maxLineLength) {
                $end = $joinedEnd;
                continue;
            }

            $breaks[$operator] = $indent;
            $childColumn = $indent + $operatorLength + 1;

            // A string expression that doesn't fit on its own line is laid out as a string, at the same indent.
            if (
                $this->reflowStringExpressions && $this->isConcatenation($phpcsFile, $child)
                && ($childColumn + $child->flatLength + $childTrailing) > $this->maxLineLength
            ) {
                $helper = new StringHelper($phpcsFile);
                if ($helper->read($child)) {
                    $code = $helper->layout($childColumn, $indent, $childTrailing, $this->maxLineLength);
                    $this->delegated[$child->start] = [$child, str_replace("\n", $phpcsFile->eolChar, $code)];
                    $lastNewline = strrpos($code, "\n");
                    $end = $lastNewline === false
                        ? $childColumn + mb_strlen($code)
                        : mb_strlen(substr($code, ($lastNewline + 1)));
                    continue;
                }
            }

            $end = $this->layout(
                $phpcsFile,
                $child,
                $childColumn,
                $indent,
                $indent,
                $childTrailing,
                $inCondition,
                $breaks,
            );
        }

        return $end;
    }

    /**
     * Find the operators a node's lines break at now, before or after them, and whether a group's parentheses have a
     * line break inside them.
     *
     * @param File $phpcsFile The file being scanned.
     * @param ExpressionNode $node The node.
     * @param array<int, true> $current The operators with a line break before or after them.
     * @param bool $misplaced Set if a group's parentheses don't hug its contents.
     */
    private function findCurrentBreaks(File $phpcsFile, ExpressionNode $node, array &$current, bool &$misplaced): void
    {
        if ($this->isDelegated($node)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        foreach ($node->operators as $operator) {
            $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($operator - 1), null, true);
            $next = $phpcsFile->findNext(T_WHITESPACE, ($operator + 1), null, true);
            if ($previous !== false && $tokens[$previous]['line'] !== $tokens[$operator]['line']) {
                $current[$operator] = true;
            }

            if ($next !== false && $tokens[$next]['line'] !== $tokens[$operator]['line']) {
                $current[$operator] = true;
            }
        }

        if ($node->kind === ExpressionNode::GROUP && is_int($node->opener) && is_int($node->closer)) {
            $inner = $node->children[0];
            $misplaced = $misplaced || $tokens[$node->opener]['line'] !== $tokens[$inner->start]['line']
                || $tokens[$node->closer]['line'] !== $tokens[$inner->end]['line'];
        }

        foreach ($node->children as $child) {
            $this->findCurrentBreaks($phpcsFile, $child, $current, $misplaced);
        }
    }

    /**
     * Set the whitespace around each operator and inside each group's parentheses to match the layout.
     *
     * @param File $phpcsFile The file being fixed.
     * @param ExpressionNode $node The node.
     * @param array<int, int> $breaks The operators to break the line before, with each one's indent.
     */
    private function applyLayout(File $phpcsFile, ExpressionNode $node, array $breaks): void
    {
        if ($this->isDelegated($node)) {
            return;
        }

        foreach ($node->operators as $index => $operator) {
            $before = isset($breaks[$operator]) ? $phpcsFile->eolChar . str_repeat(' ', $breaks[$operator]) : ' ';
            $this->setGap($phpcsFile, $node->children[$index]->end, $operator, $before);
            $this->setGap($phpcsFile, $operator, $node->children[($index + 1)]->start, ' ');
        }

        if ($node->kind === ExpressionNode::GROUP && is_int($node->opener) && is_int($node->closer)) {
            $this->setGap($phpcsFile, $node->opener, $node->children[0]->start, '');
            $this->setGap($phpcsFile, $node->children[0]->end, $node->closer, '');
        }

        foreach ($node->children as $child) {
            $this->applyLayout($phpcsFile, $child, $breaks);
        }
    }

    /**
     * Check whether a node is a string expression laid out as a string, whose line breaks aren't checked here.
     *
     * @param ExpressionNode $node The node.
     * @return bool True if it is.
     */
    private function isDelegated(ExpressionNode $node): bool
    {
        return isset($this->delegated[$node->start]) && $this->delegated[$node->start][0] === $node;
    }

    /**
     * Replace the whitespace between two tokens, if it isn't what's wanted already.
     *
     * @param File $phpcsFile The file being fixed.
     * @param int $from The token before the gap.
     * @param int $to The token after the gap.
     * @param string $content The whitespace wanted.
     */
    private function setGap(File $phpcsFile, int $from, int $to, string $content): void
    {
        if ($phpcsFile->getTokensAsString(($from + 1), ($to - $from - 1)) !== $content) {
            FixerHelper::setBetween($phpcsFile, $from, $to, $content);
        }
    }
}
