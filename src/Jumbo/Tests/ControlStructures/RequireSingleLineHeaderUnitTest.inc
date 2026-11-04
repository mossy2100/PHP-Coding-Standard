<?php

// Single-line headers are fine.
foreach ($items as $item) {
    echo $item;
}

// A foreach header that fits.
foreach (
    [$a, $b] as $value
) {
    echo $value;
}

// A foreach header containing a multi-line array, which fits once collapsed.
foreach (
    [
        $m->inv(),
        $m->pow(-1),
    ] as $inverse
) {
    echo $inverse;
}

// A for header.
for (
    $i = 0;
    $i < 10;
    $i++
) {
    echo $i;
}

// A switch header.
switch (
    $value
) {
    default:
        break;
}

// A match header.
$label = match (
    $size
) {
    default => 'any',
};

// A wrapped method chain.
foreach (
    $repository
        ->findAll() as $item
) {
    echo $item;
}

// Too long for one line.
foreach (
    $this->someVeryLongRepositoryName->findEverythingMatchingTheseCriteria($firstCriterion, $second) as $key => $value
) {
    echo $value;
}

// Contains a comment.
foreach (
    $items as $item // Each item.
) {
    echo $item;
}

// Contains a closure.
foreach (
    array_map(static function (int $x): int {
        return $x * 2;
    }, $numbers) as $doubled
) {
    echo $doubled;
}

// An if header is left to Slevomat.
if (
    $a
) {
    echo $a;
}
