<?php

// === VALID ===

class Valid
{
    // A hooked property declared in the class body.
    public string $title {
        set => trim($value);
        get => strtoupper($this->title);
    }

    public function __construct(
        // A single short hook on one line, as PER Coding Style 3.1 allows.
        public string $name { set => ucfirst($value); },
        // A get hook, with asymmetric visibility.
        public private(set) string $code { get => strtoupper($this->code); },
        // A short body containing brackets and a call.
        public array $tags { set => array_map(fn($tag) => trim($tag), $value); },
        // No hooks, with a default value.
        private readonly int $age = 0,
    ) {
    }

    // A typed method parameter followed by a closure, which isn't a hook.
    public function run(Foo $foo, int $count): array
    {
        return array_map(static function (int $value) use ($count): int {
            return $value * $count;
        }, $foo->values);
    }
}

// === ERRORS ===

class Invalid
{
    public function __construct(
        // TooComplex: a block body.
        public string $name { set { $this->name = trim($value); } },
        // TooComplex: two hooks.
        public string $label { get => $this->label; set => ucfirst($value); },
        // TooComplex: over several lines.
        public string $code {
            set => strtoupper($value);
        },
        // TooComplex: a block body over several lines.
        public string $title {
            set {
                $this->title = trim($value);
            }
        },
    ) {
    }
}

// A closure as a default value (PHP 8.5) has braces of its own, which are not a hook list.
class ClosureDefaults
{
    public \Closure $handler = static function (int $value): int {
        return $value * 2;
    };

    public function __construct(
        public \Closure $promoted = static function (int $value): int {
            return $value * 2;
        },
    ) {}
}
