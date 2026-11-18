<?php

/**
 * A blank line inside the file docblock, empty or only spaces, is reported. A line with a star isn't.

 * The second paragraph.
 *
   
 * @package Vendor\Package
 */

namespace Vendor\Package;

/**
 * A class docblock isn't part of the header, so its blank line isn't checked.

 * The second paragraph.
 */
class Example
{
}
