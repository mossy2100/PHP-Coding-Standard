<?php

// === VALID ===

class Valid
{
    // Valid: a hooked property declared in the class body.
    public string $title {
        set => trim($value);
    }

    // Valid: promoted parameters without hooks, including one with a default value.
    public function __construct(
        public string $name,
        private readonly int $age = 0,
        protected array $tags = ['a', 'b'],
    ) {
    }

    // Valid: a typed method parameter followed by a closure, which isn't a hook.
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
        // PromotedPropertyHook: a multi-line hook.
        public string $name {
            set {
                $this->name = trim($value);
            }
        },
        // PromotedPropertyHook: a single short hook on one line, which PER 3.0 allows.
        public string $label { set => ucfirst($value); },
        // Valid: a promoted parameter without a hook, after hooked ones.
        public int $count,
        // PromotedPropertyHook: asymmetric visibility with a get hook.
        public private(set) string $code { get => strtoupper($this->code); },
    ) {
    }
}
