<?php

// === VALID ===

switch ($a) {
    case 1:
        foo();
        break;
    default:
        bar();
        break;
}

switch ($a) {
    case 1:
        return 1;
    case 2:
    case 3:
}

switch ($a) {
    case 1:
        if ($b) {
            return 1;
        } else {
            throw new Exception();
        }
}

switch ($a) {
    default:
        // Nothing to do.
}

switch (true) {
    case (
        $a
        && $b
    ):
        foo();
        break;
    case ($a
        && $b
    ):
        exit(1);
}

switch ($a) {
    case 1:
        switch ($b) {
            case 2:
                return 2;
        }

        break;
}

// === INVALID ===

switch ($a) {
    case 1:
        foo();
        break;
    default:
        bar();
}

switch ($a) {
    case 1:
        foo();
}

switch ($a) {
    case 1:
        if ($b) {
            return 1;
        }
}

switch ($a) {
    default:
        bar(); }

switch (true) {
    case ($a
        && $b):
        foo();
        break;
    case ($a
        && $b    ):
        foo();
        break;
    case $a
        && $b:
        foo();
        break;
    case $a
        && $b /* Both. */:
        foo();
        break;
    case ($a
        && $b /* Both. */):
        foo();
        break;
}
