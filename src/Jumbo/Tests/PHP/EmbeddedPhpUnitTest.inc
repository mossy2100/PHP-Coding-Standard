<div>
    <?php
    foo();
    if ($a) {
        bar();
    }
    $b = [
        1,
    ];
    $c = $d
        + 1;

    baz();

    ?>
</div>
<?php foo(); ?>
<?php declare(ticks=1) ?>
<p><?= $x ?></p>
<p><?= $x; ?></p>
<div>
    <?php if ($a) { ?>
        <p>x</p>
    <?php } ?>
</div>
<!-- The code one level past the tags, on the first line and a later one. -->
<div>
    <?php
        foo();
        bar();
    ?>
</div>
<div>
    <?php
    foo();
        bar();
    ?>
</div>
<!-- Code on a tag's line. -->
<div>
    <?php if ($a) {
        foo();
    } ?>
</div>
<div>
    <?php foo();
    ?>
</div>
<div>
    <?php
    foo(); ?>
</div>
<!-- Markup on a tag's line. -->
<p><?php
foo();
?></p>
<!-- A short echo tag over several lines, in either layout. -->
<div>
    <?= foo(
        1,
    ) ?>
</div>
<div>
    <?=
    foo()
    ?>
</div>
<!-- A closing tag not level with its opening tag. -->
<div>
    <?php
    foo();
?>
</div>
<div>
    <?php
    foo();
        ?>
</div>
