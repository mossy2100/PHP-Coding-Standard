<?php

// === VALID ===

class Valid
{
    public string $a = 'x' {
        get => $this->a;
    }

    public string $b {
        get {
            return $this->b;
        }

        set => $value;
    }

    public string $c { get => strtoupper($this->c); }

    public string $d {
        set => array_map(fn($item) => trim($item), $value);
    }

    public function __construct(public string $e { set => trim($value); }) {}
}

// === INVALID ===

class Invalid
{
    public string $a
    {
        get => $this->a;
    }

    public string $b  {
        get => $this->b;
    }

    public string $c {
        get => $this->c; }

    public string $d {
        get => $this->d;
    } // Why.

    public string $e {
        get  =>  $this->e;
    }

    public string $f {
        get =>
            $this->f;
    }

    public string $g {get => $this->g;}
}

// === Bodiless lists (PER Coding Style 3.1, section 4.10) ===

interface Bodiless
{
    public string $valid { get; set; }

    public string $noSpaces {get;}

    public string $spaceBeforeSemicolon { get ; }

    public string $doubleSpaces {  get;  set;  }

    public string $multiLine {
        get;
        set;
    }
}

abstract class MixedBodies
{
    abstract public string $valid {
        get => ucfirst($this->valid);
        set;
    }

    abstract public string $spaceBeforeSemicolon {
        get => ucfirst($this->spaceBeforeSemicolon);
        set ;
    }
}

class ReferenceHooks
{
    // A space after a by-reference hook's & is reported, as for a by-reference parameter.
    public array $allowed = [] {
        & get => $this->allowed;
    }

    // Checked by default.
    public array $spaced = [] {
        & get => $this->spaced;
    }

    public array $inline = [] { & get => $this->inline; }

    public array $unspaced = [] {
        &get => $this->unspaced;
    }
    // End of this case.
}

class HookBodies
{
    // A long-form hook body on one line.
    public string $oneLine {
        get { return $this->oneLine; }
    }

    // The whole list on one line, which is only allowed for a single short hook.
    public string $allOnOne { get { return $this->allOnOne; } }

    // The closing brace after the body's last statement.
    public string $closerAfterBody {
        get {
            return $this->closerAfterBody; }
    }

    // The body starting after the opening brace.
    public string $bodyAfterOpener {
        get { return $this->bodyAfterOpener;
        }
    }

    // Parameters don't change anything.
    public string $withParameters {
        set(string $value) { $this->withParameters = $value; }
    }

    // Two hooks on one line.
    public string $twoOnOneLine {
        get { return 'a'; } set { $this->twoOnOneLine = $value; }
    }

    // A short hook and a hook with a body, inline.
    public string $mixedInline { get => 'a'; set { $this->mixedInline = $value; } }

    // An empty body, inline.
    public string $emptyInline { set {} }

    // A body of only a comment.
    public string $commentBody {
        set { /* Nothing. */ }
    }

    // A comment in the way can't be fixed.
    public string $commentInTheWay {
        get { /* Why. */ return 'a';
        }
    }
}

class ValidHookBodies
{
    public string $full {
        get {
            return $this->full;
        }

        set(string $value) {
            $this->full = $value;
        }
    }

    // An empty body has nothing to move.
    public string $empty {
        get => 'a';
        set {}
    }

    public string $emptyWithSpace {
        get => 'a';
        set { }
    }

    // A comment may follow the opening brace.
    public string $commentAfterOpener {
        get { // Why.
            return 'a';
        }
    }

    // The braces of a short hook's expression aren't a hook's body.
    public string $matchBody {
        get => match (true) {
            default => 'a',
        };
    }

    // Only a {} body rules out an inline list.
    public string $inlineShort { get => 'a'; }

    // The space before a hook's own brace.
    public string $braceOff {
        get{
            return 'a';
        }
    }

    // Checked by default.
    public string $braceOne {
        get {
            return 'a';
        }
        set(string $value) {
            $this->braceOne = $value;
        }
    }

    public string $braceNone {
        get{
            return 'a';
        }
        set(string $value)  {
            $this->braceNone = $value;
        }
    }

    public string $braceNewLine {
        get
        {
            return 'a';
        }
        set(string $value)
        {
            $this->braceNewLine = $value;
        }
    }

    public string $braceEmpty {
        set(string $value){}
    }

    public string $braceComment {
        get /* A comment. */ {
            return 'a';
        }
    }

    public array $braceReference = [] {
        &get{
            return $this->braceReference;
        }
    }
    // End of this case.
}

final class BlankLinesBetweenHooks
{
    // No blank line, or one, between hooks: both pass.
    public string $none {
        get {
            return $this->none;
        }
        set {
            $this->none = $value;
        }
    }

    public string $one {
        get {
            return $this->one;
        }

        set {
            $this->one = $value;
        }
    }

    // Two blank lines: reported, and fixed to one.
    public string $two {
        get => $this->two;


        set => $value;
    }

    // Two blank lines with a comment between: reported, not fixed.
    public string $commented {
        get => $this->commented;

        // Why.

        set => $value;
    }
}

// Checked by default.
final class HookParameterSpacing
{
    public string $valid {
        set(string $value) {
            $this->valid = $value;
        }
    }

    public string $spaceBefore {
        set (string $value) => trim($value);
    }

    public string $spaceInside {
        set( string $value ) {
            $this->spaceInside = $value;
        }
    }

    public string $split {
        set(
            string $value,
        ) => trim($value);
    }
}
// End of this case.
