<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\KTP;
use Carbon\Carbon;

test('README: Quick checks — matchBirthDate and matchGender', function () {
    $referenceDate = Carbon::parse('2026-01-01');
    $query = KTP::nik('3315131501901235')->asOf($referenceDate);

    expect($query->matchBirthDate('1990-01-15'))->toBeTrue()
        ->and($query->matchGender(Gender::Male))->toBeTrue()
        ->and($query->matchGender('male'))->toBeTrue();
});

test('README: Quick checks — matchAge and matchAtLeastYears need asOf pivot', function () {
    $query = KTP::nik('3315131501901235')->asOf(Carbon::parse('2026-01-01'));

    expect($query->matchAge(35))->toBeTrue()
        ->and($query->matchAtLeastYears(21))->toBeTrue();
});
