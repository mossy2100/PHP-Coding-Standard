<?php

namespace Vendor\Package;

// Valid: on one line, or one name per line.
use Vendor\Package\{ClassA as A, ClassB, ClassC as C};
use Vendor\Package\SomeNamespace\{
    SubnamespaceOne\ClassA,
    SubnamespaceOne\ClassB,
    ClassZ,
};
use function Vendor\Package\{
    functionA,
    functionB, // A comment after a name.
};

// The first name on the opening brace's line.
use Vendor\First\{ClassA,
    ClassB,
};

// Two names on one line.
use Vendor\Two\{
    ClassA, ClassB,
    ClassC,
};

// The closing brace after the last name.
use Vendor\Closer\{
    ClassA,
    ClassB, };

// All three.
use const Vendor\All\{CONSTANT_A, CONSTANT_B,
    CONSTANT_C};

// A comment in the way isn't fixed.
use Vendor\Commented\{ /* Why. */ ClassA,
    ClassB,
};

// A space just inside the braces of a group use on one line.
use Vendor\Spaced\{ ClassA as A, ClassB };
use function Vendor\Spaced\{functionA, functionB  };
use const Vendor\Spaced\{  CONSTANT_A};

// No space there is valid.
use Vendor\Tight\{ClassA as A, ClassB, ClassC as C};
