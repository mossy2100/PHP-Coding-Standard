<?php

declare(strict_types=1);

namespace Jumbo\Helpers;

use PHP_CodeSniffer\Files\File;

/**
 * Helper class for Jumbo.Formatting.ExpressionFormat's string expressions: reading a chain of `.` operations, or a
 * string literal, into the fewest strings it can be written as, and laying them out.
 *
 * An expression is read into segments, each either text, as a list of atoms, or an operand that can't be interpolated,
 * as its code. Each pair of segments is joined by a `.`.
 */
class StringHelper
{
    /**
     * The number of atoms in each string literal read, by position.
     *
     * @var array<int, int>
     */
    private array $atomCounts = [];

    /**
     * The segments read.
     *
     * @var list<string|list<StringAtom>>
     */
    private array $segments = [];

    /**
     * Constructor.
     *
     * @param File $phpcsFile The file being scanned.
     */
    public function __construct(private readonly File $phpcsFile) {}

    /**
     * Read a chain of `.` operations, or a lone operand, into segments.
     *
     * @param ExpressionNode $node The node.
     * @return bool False if it can't be rewritten, as it has a string with `${` in it.
     */
    public function read(ExpressionNode $node): bool
    {
        return $this->readNode($node);
    }

    /**
     * Get the segments read, with any text that's just one variable from an operand turned back into that operand.
     *
     * @return list<string|list<StringAtom>> The segments.
     */
    public function segments(): array
    {
        $segments = [];
        foreach ($this->segments as $segment) {
            if (is_array($segment) && count($segment) === 1 && $segment[0]->operand !== '') {
                $segment = $segment[0]->operand;
            }

            $segments[] = $segment;
        }

        return $segments;
    }

    /**
     * Write the segments read on one line.
     *
     * @return string The code.
     */
    public function flat(): string
    {
        $parts = [];
        foreach ($this->segments() as $segment) {
            $parts[] = is_string($segment) ? $segment : $this->render($segment);
        }

        return implode(' . ', $parts);
    }

    /**
     * Lay the segments read out over as few lines as they fit on, filling each line.
     *
     * Each line breaks before the last `.` that fits on it. A string that doesn't fit on a line of its own is split
     * before the last word or variable that fits, or, if there's none, after the last character that fits.
     *
     * @param int $column The number of characters before the expression on its first line.
     * @param int $indent The indent of each new line.
     * @param int $trailing The number of characters after the expression on its last line.
     * @param int $maxLineLength The maximum line length.
     * @return string The code.
     */
    public function layout(int $column, int $indent, int $trailing, int $maxLineLength): string
    {
        $segments = $this->segments();
        $code = '';
        $end = $column;
        $lineHasCode = false;
        $lastIndex = count($segments) - 1;
        $index = 0;
        while ($index <= $lastIndex) {
            $segment = $segments[$index];
            $text = is_string($segment) ? $segment : $this->render($segment);
            $joiner = $lineHasCode ? ' . ' : '';
            $needed = mb_strlen("{$joiner}{$text}") + ($index === $lastIndex ? $trailing : 0);
            $isUnsplittable = is_string($segment) || count($segment) < 2;
            if (($end + $needed) <= $maxLineLength || (!$lineHasCode && $isUnsplittable)) {
                $code .= "{$joiner}{$text}";
                $end += mb_strlen("{$joiner}{$text}");
                $lineHasCode = true;
                $index++;
                continue;
            }

            if (!$lineHasCode && is_array($segment)) {
                // A string too long for a line of its own is split, and the rest goes on the next line.
                $count = $this->splitPoint($segment, $end, $maxLineLength);
                $code .= $this->render(array_slice($segment, 0, $count));
                $segments[$index] = array_slice($segment, $count);
            }

            $code .= "\n" . str_repeat(' ', $indent) . '. ';
            $end = $indent + 2;
            $lineHasCode = false;
        }

        return $code;
    }

    /**
     * Find how many of a string's atoms go on the line, when it's too long to fit.
     *
     * It breaks after the last space that fits, or, if there's none, after the last variable that fits, or, if there's
     * none, after the last character that fits.
     *
     * @param list<StringAtom> $atoms The atoms.
     * @param int $column The number of characters before the string.
     * @param int $maxLineLength The maximum line length.
     * @return int The number of atoms, from 1, and less than all of them.
     */
    private function splitPoint(array $atoms, int $column, int $maxLineLength): int
    {
        $lastFit = 0;
        $lastSpaceFit = 0;
        $lastVariableFit = 0;
        $count = count($atoms);
        for ($i = 1; $i < $count; $i++) {
            if (($column + mb_strlen($this->render(array_slice($atoms, 0, $i)))) > $maxLineLength) {
                break;
            }

            $lastFit = $i;
            if ($atoms[($i - 1)]->isSpace() && !$atoms[$i]->isSpace()) {
                $lastSpaceFit = $i;
            }

            if ($atoms[($i - 1)]->kind === StringAtom::VARIABLE) {
                $lastVariableFit = $i;
            }
        }

        $fit = $lastSpaceFit > 0 ? $lastSpaceFit : ($lastVariableFit > 0 ? $lastVariableFit : $lastFit);

        return max(1, $fit);
    }

    /**
     * Write a string's atoms as one string literal.
     *
     * A string that's exactly one literal as it's written keeps its quotes, as Squiz.Strings.DoubleQuoteUsage decides
     * those. Any other is single-quoted if it can be.
     *
     * @param list<StringAtom> $atoms The atoms.
     * @return string The code.
     */
    public function render(array $atoms): string
    {
        $sources = array_unique(array_map(static fn(StringAtom $atom): int => $atom->source, $atoms));
        $source = count($sources) === 1 ? reset($sources) : -1;
        $isSingle = $source >= 0 && ($this->atomCounts[$source] ?? 0) === count(
            $atoms,
        ) ? $this->phpcsFile->getTokens()[$source]['content'][0] === "'" : array_all(
            $atoms,
            static fn(StringAtom $atom): bool => $atom->single !== null,
        );

        return $isSingle ? $this->renderSingle($atoms) : $this->renderDouble($atoms);
    }

    /**
     * Write atoms as a single-quoted string.
     *
     * @param list<StringAtom> $atoms The atoms, each of which can be single-quoted.
     * @return string The code.
     */
    private function renderSingle(array $atoms): string
    {
        $code = '';
        foreach ($atoms as $index => $atom) {
            $text = (string) $atom->single;
            $next = isset($atoms[($index + 1)]) ? (string) $atoms[($index + 1)]->single : "'";

            // A backslash written alone would escape the next one, or the quote.
            if ($text === '\\' && ($next === '' || $next[0] === '\\' || $next[0] === "'")) {
                $text = '\\\\';
            }

            $code .= $text;
        }

        return "'{$code}'";
    }

    /**
     * Write atoms as a double-quoted string, with braces around a variable only where it needs them.
     *
     * @param list<StringAtom> $atoms The atoms.
     * @return string The code.
     */
    private function renderDouble(array $atoms): string
    {
        $code = '';
        foreach ($atoms as $index => $atom) {
            $next = isset($atoms[($index + 1)]) ? $atoms[($index + 1)]->double : '';
            $text = $atom->double;
            if (
                $atom->kind === StringAtom::TEXT && $text === '$' && preg_match('/^[A-Za-z_\x80-\xff{]/', $next) === 1
            ) {
                $text = '\\$';
            }

            $previous = substr($code, -1);
            $needsBraces = preg_match('/^([A-Za-z0-9_\x80-\xff\[]|->|\?->)/', $next) === 1 || $previous === '{';
            if ($atom->kind === StringAtom::VARIABLE && $atom->simple && $needsBraces) {
                $text = "{{$text}}";
            }

            $code .= $text;
        }

        return "\"{$code}\"";
    }

    /**
     * Read a node: a chain of `.` operations into its operands, or an operand.
     *
     * @param ExpressionNode $node The node.
     * @return bool False if it can't be rewritten.
     */
    private function readNode(ExpressionNode $node): bool
    {
        $tokens = $this->phpcsFile->getTokens();
        if ($node->kind === ExpressionNode::LEVEL && $tokens[$node->operators[0]]['code'] === T_STRING_CONCAT) {
            foreach ($node->children as $child) {
                if (!$this->readNode($child)) {
                    return false;
                }
            }

            return true;
        }

        return $this->readOperand($node->start, $node->end);
    }

    /**
     * Read an operand into text, if it can be written in a string, or as an operand.
     *
     * @param int $start The position of its first token.
     * @param int $end The position of its last token.
     * @return bool False if it can't be rewritten.
     */
    private function readOperand(int $start, int $end): bool
    {
        $tokens = $this->phpcsFile->getTokens();

        // A string conversion is redundant, as `.` converts its operands to strings.
        if ($tokens[$start]['code'] === T_STRING_CAST) {
            $start = (int) $this->phpcsFile->findNext(T_WHITESPACE, ($start + 1), ($end + 1), true);
        }

        if ($this->isToStringCall($end) && ($end - 4) >= $start) {
            $end -= 4;
        }

        // Grouping parentheses around a chain of `.` operations aren't needed, as `.` is associative.
        if (
            $tokens[$start]['code'] === T_OPEN_PARENTHESIS && ($tokens[$start]['parenthesis_closer'] ?? null) === $end
            && ExpressionHelper::isGrouping($this->phpcsFile, $start)
        ) {
            $innerStart = $this->phpcsFile->findNext(T_WHITESPACE, ($start + 1), $end, true);
            $innerEnd = $this->phpcsFile->findPrevious(T_WHITESPACE, ($end - 1), $start, true);
            $inner = is_int($innerStart) && is_int($innerEnd)
                ? ExpressionHelper::parse($this->phpcsFile, $innerStart, $innerEnd)
                : null;
            if (
                $inner !== null && $inner->kind === ExpressionNode::LEVEL
                && $tokens[$inner->operators[0]]['code'] === T_STRING_CONCAT
            ) {
                return $this->readNode($inner);
            }

            // Nor are they around one string literal or variable, or more parentheses, which are checked the same way.
            if (
                $inner !== null && $inner->kind !== ExpressionNode::LEVEL
                && ($inner->start === $inner->end || $inner->opener === $inner->start
                    || $this->readVariable($inner->start, $inner->end) !== null)
            ) {
                return $this->readOperand($inner->start, $inner->end);
            }
        }

        if ($start === $end) {
            $atoms = $this->readLiteral($start);
            if ($atoms === null) {
                return false;
            }

            if ($atoms !== []) {
                $this->addText($atoms);
                return true;
            }
        }

        $code = $this->code($start, $end);
        $variable = $this->readVariable($start, $end);
        if ($variable !== null) {
            $this->addText([new StringAtom(StringAtom::VARIABLE, null, $variable[0], -1, $variable[1], $code)]);
            return true;
        }

        $this->segments[] = $code;

        return true;
    }

    /**
     * Check whether a token ends a `->__toString()` call.
     *
     * @param int $end The position of the token.
     * @return bool True if it does.
     */
    private function isToStringCall(int $end): bool
    {
        $tokens = $this->phpcsFile->getTokens();

        return $end >= 4 && $tokens[$end]['code'] === T_CLOSE_PARENTHESIS
            && $tokens[($end - 1)]['code'] === T_OPEN_PARENTHESIS && $tokens[($end - 2)]['code'] === T_STRING
            && strtolower($tokens[($end - 2)]['content']) === '__tostring'
            && $tokens[($end - 3)]['code'] === T_OBJECT_OPERATOR;
    }

    /**
     * Add text to the last segment, or start a new one.
     *
     * @param list<StringAtom> $atoms The text.
     */
    private function addText(array $atoms): void
    {
        $last = array_key_last($this->segments);
        if ($last !== null && is_array($this->segments[$last])) {
            $this->segments[$last] = [...$this->segments[$last], ...$atoms];
            return;
        }

        $this->segments[] = $atoms;
    }

    /**
     * Get an operand's code on one line.
     *
     * @param int $start The position of its first token.
     * @param int $end The position of its last token.
     * @return string The code.
     */
    private function code(int $start, int $end): string
    {
        $tokens = $this->phpcsFile->getTokens();
        $code = '';
        for ($i = $start; $i <= $end; $i++) {
            $code .= $tokens[$i]['code'] === T_WHITESPACE ? ' ' : $tokens[$i]['content'];
        }

        return $code;
    }

    /**
     * Read a token as text, if it's a string literal or a decimal integer.
     *
     * @param int $ptr The position of the token.
     * @return list<StringAtom>|null The atoms, an empty list if it isn't text, or null if it can't be rewritten.
     */
    private function readLiteral(int $ptr): ?array
    {
        $token = $this->phpcsFile->getTokens()[$ptr];
        $content = $token['content'];
        $atoms = [];
        if ($token['code'] === T_LNUMBER && preg_match('/^(0|[1-9][0-9]*)$/', $content) === 1) {
            foreach (str_split($content) as $digit) {
                $atoms[] = new StringAtom(StringAtom::TEXT, $digit, $digit);
            }

            return $atoms;
        }

        if ($token['code'] === T_CONSTANT_ENCAPSED_STRING && $content[0] === "'") {
            $atoms = $this->readSingleQuoted(substr($content, 1, -1), $ptr);
        } elseif ($token['code'] === T_CONSTANT_ENCAPSED_STRING && $content[0] === '"') {
            $atoms = $this->readDoubleQuoted(substr($content, 1, -1), $ptr);
        } elseif ($token['code'] === T_DOUBLE_QUOTED_STRING) {
            $atoms = $this->readInterpolated($content, $ptr);
            if ($atoms === null) {
                return null;
            }
        } else {
            return [];
        }

        $this->atomCounts[$ptr] = count($atoms);

        return $atoms;
    }

    /**
     * Read the text of a single-quoted string.
     *
     * @param string $content The text between the quotes.
     * @param int $source The position of the literal.
     * @return list<StringAtom> The atoms.
     */
    private function readSingleQuoted(string $content, int $source): array
    {
        $atoms = [];
        $length = strlen($content);
        for ($i = 0; $i < $length; $i++) {
            if ($content[$i] === '\\' && $i + 1 < $length && in_array($content[($i + 1)], ['\\', "'"], true)) {
                $char = $content[($i + 1)];
                $atoms[] = new StringAtom(StringAtom::TEXT, "\\{$char}", self::escapeDouble($char), $source);
                $i++;
                continue;
            }

            $char = self::character($content, $i);
            $atoms[] = new StringAtom(StringAtom::TEXT, $char, self::escapeDouble($char), $source);
            $i += strlen($char) - 1;
        }

        return $atoms;
    }

    /**
     * Read text from a double-quoted string, which has no variables in it.
     *
     * @param string $content The text.
     * @param int $source The position of the literal.
     * @return list<StringAtom> The atoms.
     */
    private function readDoubleQuoted(string $content, int $source): array
    {
        $atoms = [];
        $pattern = '/\G(?:\\\\[0-7]{1,3}|\\\\x[0-9A-Fa-f]{1,2}|\\\\u\{[0-9A-Fa-f]+\}|\\\\[\s\S]|[\x00-\x7f]|[\xc0-\xff]'
            . '[\x80-\xbf]*)/';
        $offset = 0;
        $length = strlen($content);
        while ($offset < $length && preg_match($pattern, $content, $match, 0, $offset) === 1) {
            $text = $match[0];
            $offset += strlen($text);
            $single = match ($text) {
                '\\\\' => '\\\\',
                '\\"' => '"',
                '\\$' => '$',
                "'" => "\\'",
                default => $text[0] === '\\' && strlen($text) > 1 ? null : $text,
            };
            $atoms[] = new StringAtom(StringAtom::TEXT, $single, $text, $source);
        }

        return $atoms;
    }

    /**
     * Read the text and variables of a double-quoted string with variables in it.
     *
     * @param string $content The string, with its quotes.
     * @param int $source The position of the literal.
     * @return list<StringAtom>|null The atoms, or null if it uses `${`.
     */
    private function readInterpolated(string $content, int $source): ?array
    {
        $parts = token_get_all("<?php {$content}");
        $atoms = [];
        $count = count($parts);
        for ($i = 1; $i < $count; $i++) {
            $part = $parts[$i];
            $id = is_array($part) ? $part[0] : null;
            $text = is_array($part) ? $part[1] : $part;
            if ($id === T_DOLLAR_OPEN_CURLY_BRACES) {
                return null;
            }

            if ($id === T_ENCAPSED_AND_WHITESPACE) {
                $atoms = [...$atoms, ...$this->readDoubleQuoted($text, $source)];
                continue;
            }

            if ($id === T_CURLY_OPEN) {
                [$variable, $i] = $this->readBracedInterpolation($parts, $i);
                $atoms[] = new StringAtom(StringAtom::VARIABLE, null, $variable, $source);
                continue;
            }

            if ($id === T_VARIABLE) {
                [$variable, $i] = $this->readSimpleInterpolation($parts, $i);
                $atoms[] = new StringAtom(StringAtom::VARIABLE, null, $variable, $source, true);
            }
        }

        return $atoms;
    }

    /**
     * Read a variable in braces in a double-quoted string, as in "{$a['b']}", from its { to the matching }.
     *
     * @param array<int, array{int, string, int}|string> $parts The string's tokens, from token_get_all().
     * @param int $index The position of its {.
     * @return array{string, int} The variable with its braces, and the position of its }.
     */
    private function readBracedInterpolation(array $parts, int $index): array
    {
        $count = count($parts);
        $depth = 0;
        $variable = '';
        for (; $index < $count; $index++) {
            $piece = is_array($parts[$index]) ? $parts[$index][1] : $parts[$index];
            $variable .= $piece;
            $isOpener = is_array($parts[$index]) ? $parts[$index][0] === T_CURLY_OPEN : in_array(
                $piece,
                ['{', '['],
                true,
            );
            $depth += $isOpener ? 1 : 0;
            $depth -= !is_array($parts[$index]) && in_array($piece, ['}', ']'], true) ? 1 : 0;
            if ($depth === 0) {
                break;
            }
        }

        return [$variable, $index];
    }

    /**
     * Read a variable without braces in a double-quoted string, as in "$a->b" or "$a[0]", with any property reads,
     * nullsafe property reads and array accesses after it.
     *
     * @param array<int, array{int, string, int}|string> $parts The string's tokens, from token_get_all().
     * @param int $index The position of its variable.
     * @return array{string, int} The variable as written, and the position of its last token.
     */
    private function readSimpleInterpolation(array $parts, int $index): array
    {
        $count = count($parts);
        $variable = is_array($parts[$index]) ? $parts[$index][1] : $parts[$index];
        while (isset($parts[($index + 1)])) {
            $next = $parts[($index + 1)];
            $nextId = is_array($next) ? $next[0] : null;
            if (in_array($nextId, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
                $variable .= "{$next[1]}{$parts[($index + 2)][1]}";
                $index += 2;
                continue;
            }

            if ($next === '[') {
                $index++;
                for (; $index < $count; $index++) {
                    $variable .= is_array($parts[$index]) ? $parts[$index][1] : $parts[$index];
                    if ($parts[$index] === ']') {
                        break;
                    }
                }

                continue;
            }

            break;
        }

        return [$variable, $index];
    }

    /**
     * Read an operand as a variable that can be interpolated: a variable, then any property reads, nullsafe property
     * reads, method calls and array accesses.
     *
     * @param int $start The position of its first token.
     * @param int $end The position of its last token.
     * @return array{string, bool}|null How it's written in a string, and whether that's without braces, or null if it
     * can't be interpolated.
     */
    private function readVariable(int $start, int $end): ?array
    {
        $tokens = $this->phpcsFile->getTokens();
        if ($tokens[$start]['code'] !== T_VARIABLE) {
            return null;
        }

        $simple = true;
        $steps = 0;
        for ($i = ($start + 1); $i <= $end; $i++) {
            $code = $tokens[$i]['code'];
            if (
                in_array($code, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)
                && ($tokens[($i + 1)]['code'] ?? null) === T_STRING
            ) {
                $i++;
                $steps++;
                if (($tokens[($i + 1)]['code'] ?? null) === T_OPEN_PARENTHESIS && $i + 1 <= $end) {
                    $i = (int) $tokens[($i + 1)]['parenthesis_closer'];
                    $simple = false;
                }

                continue;
            }

            if ($code === T_OPEN_SQUARE_BRACKET && isset($tokens[$i]['bracket_closer'])) {
                $closer = $tokens[$i]['bracket_closer'];
                $key = $this->phpcsFile->getTokensAsString(($i + 1), ($closer - $i - 1));
                $simple = $simple
                    && preg_match('/^(-?(0|[1-9][0-9]*)|\$[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)$/', $key) === 1;
                $i = $closer;
                $steps++;
                continue;
            }

            return null;
        }

        $code = $this->code($start, $end);
        $simple = $simple && $steps <= 1 && !str_contains($code, ' ');

        return [$simple ? $code : "{{$code}}", $simple];
    }

    /**
     * Write a character as it's written in a double-quoted string.
     *
     * @param string $char The character.
     * @return string The code.
     */
    private static function escapeDouble(string $char): string
    {
        return match ($char) {
            '\\' => '\\\\',
            '"' => '\\"',
            '$' => '\\$',
            default => $char,
        };
    }

    /**
     * Get the UTF-8 character at an offset.
     *
     * @param string $text The text.
     * @param int $offset The offset of its first byte.
     * @return string The character.
     */
    private static function character(string $text, int $offset): string
    {
        return preg_match('/\G(?:[\x00-\x7f]|[\xc0-\xff][\x80-\xbf]*|[\s\S])/', $text, $match, 0, $offset) === 1
            ? $match[0]
            : $text[$offset];
    }
}
