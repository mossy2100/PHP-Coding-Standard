<?php

// A case body wrapped in braces, before a default. The parent sniff crashed on this, so the file's checks stopped.
switch ($a) {
    case 2: {
        echo 2;
    }
    default:
        echo 3;
    break;
}

// Both bodies wrapped in braces.
switch ($a) {
    case 1: {
        echo 1;
        break;
    }
    default: {
        echo 2;
        break;
    }
}

// A switch after them is still checked.
switch ($a) {
    case 1:
        echo 1;
}

// A block that's only part of the body passes: one followed by a break, or by more statements.
switch ($a) {
    case 1:
        {
            echo 1;
        }
        break;
    case 2:
        {
            echo 2;
        }
        echo 3;
        break;
    default:
        break;
}

// A block followed by a comment, then the next label, wraps the body.
switch ($a) {
    case 1: {
        echo 1;
    } // No break.
    case 2:
        echo 2;
        break;
}

// A block that's part of a body doesn't stop the switch's other checks: two spaces after case, a space before the
// colon, a fall-through with no comment, a break after a block that isn't indented as the body, and a last case with
// no terminating statement.
switch ($a) {
    case 0:
        {
            echo 0;
        }
        break;
    case  1 :
        echo 1;
    case 2:
        {
            echo 2;
        }
    break;
    case 3:
        echo 3;
}
