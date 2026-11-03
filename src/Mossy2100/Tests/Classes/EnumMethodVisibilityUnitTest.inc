<?php

// === VALID ===

enum Valid
{
    case Hearts;

    public function label(): string
    {
        return $this->helper();
    }

    private function helper(): string
    {
        return 'x';
    }

    protected const int LIMIT = 1;
}

class NotAnEnum
{
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
}
