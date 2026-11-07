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
