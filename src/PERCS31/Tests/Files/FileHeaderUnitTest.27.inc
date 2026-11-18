<?php declare(strict_types=1) ?>
<?php

namespace Vendor\Package;

$name = 'World';

use Vendor\Other\Greeter;

$greeting = Greeter::hello($name);
?>
<p><?= $greeting ?></p>
