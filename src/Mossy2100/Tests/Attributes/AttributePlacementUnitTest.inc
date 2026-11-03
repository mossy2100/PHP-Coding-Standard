<?php

// === VALID ===

#[Attribute]
final class Valid
{
    #[Deprecated]
    public const int OLD = 1;

    #[Deprecated]
    public int $property = 1;

    #[Override]
    #[Deprecated]
    public function method(#[SensitiveParameter] string $password, #[Unused] int $count): void
    {
        $closure = static function (#[SensitiveParameter] string $secret): void {
        };
        $arrow = static fn(#[SensitiveParameter] string $secret): string => $secret;
    }

    public function multiLine(
        #[SensitiveParameter]
        string $password,
        int $count,
    ): void {
    }

    public function __construct(#[SensitiveParameter] private string $promoted)
    {
    }
}

#[Attribute]
function plainFunction(#[SensitiveParameter] string $password): void
{
}

// === INVALID ===

#[Attribute] final class Invalid
{
    #[Deprecated] public const int OLD = 1;

    #[Deprecated] public int $property = 1;

    #[Override] #[Deprecated] public function method(): void
    {
    }

    public function multiLine(
        #[SensitiveParameter] string $password,
        int $count,
    ): void {
    }

    #[Deprecated] /* why */ public function commented(): void
    {
    }
}

#[Attribute] function invalidFunction(): void
{
}

function anonymousClasses(): array
{
    $valid = new
        #[Attribute]
        class {
            public int $x = 1;
        };

    $validTwo = new
        #[Attribute]
        #[Deprecated]
        class {};

    $sameLine = new #[Attribute] class {};

    $notIndented = new
    #[Attribute]
    class {};

    $classOnSameLine = new
        #[Attribute] class {};

    $twoOnOneLine = new #[Attribute] #[Deprecated] class ($a) {};

    $commented = new /* why */ #[Attribute] class {};

    consume(new #[Attribute] class {});

    return [new class {}];
}
