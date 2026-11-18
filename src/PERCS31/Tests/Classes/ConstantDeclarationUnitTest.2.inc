<?php

// phpcs:set PERCS31.Classes.ConstantDeclaration checkGlobalConstants true

namespace Vendor\Package;

// An import isn't a declaration, so its spacing is left to the import sniffs.
use const Other\LIMIT;
use  const Other\MAX;
use Other\{Thing, const  OTHER_LIMIT};

// Valid.
const A = 1;
const /* A single space each side of a comment passes. */ B = 2;

// Two spaces after const, and a line break.
const  C = 3;
const
    D = 4;

// Two constants in one statement.
const E = 5, F = 6;

// phpcs:set PERCS31.Classes.ConstantDeclaration checkGlobalConstants false

// Not checked once the property is off.
const  G = 7, H = 8;
