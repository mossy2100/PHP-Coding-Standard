<?php

/**
 * A comment at the end of a header block's last line doesn't start the next block.
 */

declare(strict_types=1); // Strict.

namespace Vendor\Package; // The namespace.

use Vendor\Other\Greeter; // phpcs:ignore Some.Sniff
use Vendor\Other\Farewell; /* The last class. */

use function Vendor\Other\greet; // A function.

use const Vendor\Other\GREETING; # A constant.

echo Greeter::hello();
