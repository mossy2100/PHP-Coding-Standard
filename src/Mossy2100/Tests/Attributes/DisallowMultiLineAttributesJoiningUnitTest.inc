<?php

// === VALID ===

#[Marker, Legacy]
final class OneLine {}

#[Marker]
#[Legacy]
final class Separate {}

#[Legacy(
    'old',
)]
final class OneAttributeSplit {}

// === INVALID ===

#[
    Marker,
    Legacy,
]
final class Split {}

#[Marker, Legacy(
    'old',
)]
final class SplitArguments {}

final class Members
{
    #[
        Legacy,
        Tagged(['a', 'b']),
    ]
    public function method(): void {}
}

#[
    Marker, // Why.
    Legacy,
]
final class Commented {}
