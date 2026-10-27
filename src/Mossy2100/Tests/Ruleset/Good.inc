<?php

declare(strict_types=1);

namespace Mossy2100\Fixtures;

use Countable;
use InvalidArgumentException;
use Stringable;

use function count;
use function sprintf;

/**
 * A class that follows the Mossy2100 standard, used to check the full ruleset.
 */
final class Inventory implements Countable, Stringable
{
    public const int MAX_ITEMS = 100;
    public const int MIN_ITEMS = 0;

    public const array COLORS = ['red', 'green', 'blue'];

    public const array LIMITS = ['small' => 10, 'medium' => 50, 'large' => 100];

    /**
     * The item names.
     *
     * @var list<string>
     */
    private array $items = [];

    public string $label {
        get => $this->name . ' (' . count($this) . ')';
    }

    public private(set) int $version = 0;

    /**
     * Create an inventory.
     *
     * @param string $name The inventory name.
     */
    public function __construct(public readonly string $name)
    {
    }

    /**
     * Add an item.
     *
     * @param string $item The item name.
     * @return static This inventory.
     * @throws InvalidArgumentException If the inventory is full.
     */
    public function add(string $item): static
    {
        if (count($this->items) >= self::MAX_ITEMS) {
            throw new InvalidArgumentException(sprintf('The inventory "%s" is full.', $this->name));
        }

        $this->items[] = $item;
        $this->version++;

        return $this;
    }

    /**
     * Get the items whose names are longer than a given length.
     *
     * @param int $length The minimum length.
     * @return list<string> The matching items.
     */
    public function longerThan(int $length): array
    {
        return array_values(array_filter($this->items, static fn (string $item): bool => strlen($item) > $length));
    }

    /**
     * Describe the size of the inventory.
     *
     * @return string The size.
     */
    public function size(): string
    {
        $count = count($this->items);

        return match (true) {
            $count === 0 => 'empty',
            $count < self::LIMITS['small'] => 'small',
            $count < self::LIMITS['medium'] => 'medium',
            default => 'large',
        };
    }

    /**
     * Get a summary of the inventory.
     *
     * @return array<string, mixed> The summary.
     */
    public function summary(): array
    {
        return [
            'name' => $this->name,
            'count' => count($this->items),
            'version' => (int) $this->version,
            'samples' => [strtoupper($this->items[0] ?? ''), strtolower($this->items[1] ?? '')],
        ];
    }

    /**
     * Check whether an item is in the inventory, ignoring case.
     *
     * @param string $item The item name.
     * @return bool True if the item is found.
     */
    public function has(string $item): bool
    {
        foreach ($this->items as $existing) {
            if (strcasecmp($existing, $item) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Count the items.
     *
     * @return int The number of items.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Convert the inventory to a string.
     *
     * @return string The inventory name and items.
     */
    public function __toString(): string
    {
        return $this->name . ': ' . implode(', ', $this->items);
    }
}
