<div>
    <?php if ($a) {
        foo();
    } ?>
</div>
<div>
    <?php if (
        $a
    ) {
        foo();
    } ?>
</div>
<div>
    <?php $a = match ($b) {
        1 => 2,
        default => 3,
    }; ?>
</div>
<ul>
    <?php foreach ($a as $b) {
        foo();
    } ?>
</ul>
<div>
    <?php if ($a) { ?>
        <p>x</p>
    <?php } ?>
</div>

<!-- A body indented too far or too little past the tag. -->
<div>
    <?php if ($a) {
            foo();
    } ?>
</div>
<div>
    <?php if ($a) {
    foo();
    } ?>
</div>
<div>
    <?php $a = match ($b) {
            1 => 2,
        default => 3,
    }; ?>
</div>

<!-- A block's code may be level with its tags, or one level past them. -->
<div>
    <?php
    foo();
    if ($a) {
        foo();
    }
    ?>
</div>
<div>
    <?php
        foo();
        if ($a) {
            foo();
        }
        bar();
    ?>
</div>
<div>
    <?php
        // A comment.
        if ($a) {
            foo();
        }
    ?>
</div>

<!-- Two levels past them, and a closing tag that doesn't line up with the opening tag. -->
<div>
    <?php
            if ($a) {
                foo();
            }
    ?>
</div>
<div>
    <?php
        foo();
?>
</div>
