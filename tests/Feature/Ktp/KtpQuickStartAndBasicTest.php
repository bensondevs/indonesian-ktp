<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\KTP;

test('README: Quick start — minimal isValid', function () {
    expect(KTP::nik('3315131501901235')->isValid())->toBeTrue();
});

test('README: Basic validation', function () {
    expect(KTP::nik('3315131501901235')->isValid())->toBeTrue();
});

test('README: Usage intro — expectGender then isValid', function () {
    expect(
        KTP::nik('3315131501901235')
            ->expectGender(Gender::Male)
            ->isValid(),
    )->toBeTrue();
});
