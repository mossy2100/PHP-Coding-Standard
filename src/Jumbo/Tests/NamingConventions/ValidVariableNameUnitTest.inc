<?php

// === OUTSIDE A CLASS: Squiz reports these, so this sniff doesn't ===

$_globalVar = 1;

function plainFunction(int $_param): int
{
    $_local = 1;
    return $_param + $_local;
}

// === INSIDE A CLASS ===

class Example
{
    // Not reported: a property, checked by PERCS31.Classes.PropertyDeclaration.
    private int $_property = 1;

    private static int $_static = 1;

    // Not reported: a promoted parameter is a property too. The plain parameter is reported.
    public function __construct(public int $_promoted, int $_plain)
    {
    }

    public function method(int $_methodParam, int $camelParam): int
    {
        // Reported: a local variable.
        $_local = 1;

        // Not reported: superglobals.
        $get = $_GET['x'] ?? $_POST['x'] ?? $_SERVER['x'] ?? $_COOKIE['x'] ?? $_ENV['x'] ?? $_SESSION['x'];

        // Not reported: property access.
        $sum = $this->_property + self::$_static + static::$_static;

        // Reported: a closure parameter and local, and a foreach variable.
        $f = static function (int $_closureParam): int {
            $_closureLocal = 1;
            return $_closureParam + $_closureLocal;
        };
        foreach ([1] as $_item) {
        }

        // Not reported: Squiz reports these, as the rest of the name isn't camel caps.
        $_snake_case = 1;
        $__double = 1;
        $_ = 1;

        return $_methodParam + $camelParam + $_local + $sum + $f(1) + $_item + $get;
    }
}

enum Suit
{
    public static function make(int $_value): int
    {
        return $_value;
    }
}

trait Helper
{
    public function help(): void
    {
        $_inTrait = 1;
    }
}
