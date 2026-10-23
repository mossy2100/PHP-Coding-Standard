<?php

// === VALID: Basic properties ===

class ValidBasicProperties
{
    public string $name = 'foo';

    protected int $age = 0;

    private array $items = [];

    public static int $count = 0;

    public readonly string $id = 'abc';
}

// === VALID: Property hooks (variables inside should NOT trigger errors) ===

class ValidHookProperties
{
    public string $arrowHook {
        get => 'value';
    }

    public string $blockHook {
        get {
            $temp = 'hello';
            return $temp;
        }
        set {
            $this->blockHook = $value;
        }
    }

    public string $withDefault = 'default' {
        get => $this->withDefault;
        set => $this->withDefault = $value;
    }

    public private(set) string $aviz = 'test';

    final public string $finalHook {
        get => 'final';
    }
}

abstract class ValidAbstractProperties
{
    abstract public string $abstractProp { get; set; }
}

// === ERRORS ===

class ErrorProperties
{
    // Underscore: warning.
    public string $_underscored = 'bad';

    // VarUsed + ScopeMissing: 2 errors.
    var $oldStyle;

    // Multiple: 1 error on $first.
    public $first, $second;

    // SpacingAfterType: 0 spaces (fixable).
    public string$noSpace = '';

    // SpacingAfterType: 2 spaces (fixable).
    public string  $twoSpaces = '';

    // StaticBeforeVisibility (fixable).
    static public int $staticFirst = 0;

    // ReadonlyBeforeVisibility (fixable).
    readonly public string $readonlyFirst = 'a';

    // AvizKeywordOrder: write-visibility before read-visibility (fixable).
    protected(set) public string $avizWrong = '';

    // FinalAfterVisibility (fixable).
    public final string $finalAfter {
        get => '';
    }
}

abstract class ErrorAbstractProperties
{
    // AbstractAfterVisibility (fixable).
    public abstract string $abstractAfter { get; set; }
}

class MoreProperties
{
    // Valid: a set hook with an explicit parameter.
    public string $explicit {
        set(string $value) {
            $this->explicit = trim($value);
        }
    }

    // SpacingAfterType: the type and variable on separate lines (fixable).
    public string
        $splitType;

    // SpacingAfterType: a comment between the type and variable (not fixable).
    public string  /* note */ $commented;

    // Valid: local variables and variables in strings are not properties.
    public function run(): string
    {
        $local = 'x';
        return "Value: $local";
    }
}

enum Status
{
    // Not reported: a property in an enum is a parse error, which getMemberProperties() rejects.
    public $invalid;
}
