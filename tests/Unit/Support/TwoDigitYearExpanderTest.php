<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\Support\TwoDigitYearExpander;
use Carbon\Carbon;

test('resolves two digit year near pivot century', function () {
    $pivot = Carbon::parse('2026-06-01');
    expect(TwoDigitYearExpander::resolveUsingPivotYear(5, $pivot))->toBe(2005)
        ->and(TwoDigitYearExpander::resolveUsingPivotYear(30, $pivot))->toBe(1930);
});

test('candidate birth years within KTP age bounds can include two centuries', function () {
    $evaluatedAt = Carbon::parse('2026-09-01');
    $years = TwoDigitYearExpander::candidateBirthYearsForAmbiguousNik(9, 9, 1, $evaluatedAt);

    expect($years)->toContain(1909, 2009);
});
