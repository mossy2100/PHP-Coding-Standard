<?php

// The parent's BodyOnNextLine codes report code on a label's line, and a blank line straight after a label.
// This sniff's CommentOnCaseLine code reports a comment on a label's line.

// A blank line straight after a label is reported, and removed by the fix.
switch ($a) {
    case 1:

        foo();
        break;

    case 2:


        foo();
        break;
    case 3:

        // Comment.
        foo();
        break;
    case 4:

    case 5:
        foo();

        break;
    case 6:
        // Comment.

        foo();
        break;
    case 7:
        // Comment.
    case 8:

        break;
    default:

        foo();
        break;
}

// Code or a comment on the label's line.
switch ($a) {
    case 1: foo();
        break;
    case 2: // Comment.
        foo();
        break;
    case 3:// Comment.
        foo();
        break;
    case 4: /* Comment. */
        foo();
        break;
    case 5: /* Comment. */ foo();
        break;
    case 6: // Comment.

        foo();
        break;
    case 7: // phpcs:ignore Foo.Bar
        break;
    default: // Comment.
        foo();
        break;
}
