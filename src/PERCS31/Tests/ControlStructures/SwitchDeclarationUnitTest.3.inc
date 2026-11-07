<?php

// phpcs:disable PERCS31.ControlStructures.SwitchDeclaration.BodyOnNextLineCASE
// phpcs:disable PERCS31.ControlStructures.SwitchDeclaration.BodyOnNextLineDEFAULT
// phpcs:set PERCS31.ControlStructures.SwitchDeclaration checkBlankLineAfterLabel true

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
    default:

        break;
}
