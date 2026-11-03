<?php

// === VALID ===

enum Valid
{
    case Hearts;

    public const int SIZE = 1;

    private const int LIMIT = 1;

    const int DEFAULT = 1;

    public function label(): string
    {
        return $this->helper();
    }

    private function helper(): string
    {
        return 'x';
    }
}

class NotAnEnum
{
    protected const int LIMIT = 1;

    protected function helper(): void
    {
    }
}

// === INVALID ===

enum Invalid
{
    case Hearts;

    protected function helper(): string
    {
        return 'x';
    }

    #[Deprecated]
    protected static function make(): self
    {
        return self::Hearts;
    }

    final protected function locked(): void
    {
    }

    protected const int LIMIT = 1;

    final protected const int MAX = 2;

    #[Deprecated]
    protected const int OLD = 0;
}
