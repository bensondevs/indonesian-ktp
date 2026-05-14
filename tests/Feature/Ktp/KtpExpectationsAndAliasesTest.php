<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\KTP;
use Carbon\Carbon;

test('README: Expectations — full names', function () {
    Carbon::setTestNow('2026-01-01');

    try {
        expect(
            KTP::nik('3315131501901235')
                ->expectBirthDate('1990-01-15')
                ->expectGender(Gender::Male)
                ->expectProvince('jawa tengah')
                ->isValid(),
        )->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});

test('README: Expectations — aliases', function () {
    Carbon::setTestNow('2026-01-01');

    try {
        expect(
            KTP::nik('3315131501901235')
                ->birthDate('1990-01-15')
                ->gender(Gender::Male)
                ->province('jawa tengah')
                ->isValid(),
        )->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});

test('README: Expectations — demographic and wilayah chain', function () {
    Carbon::setTestNow('2026-01-01');

    try {
        expect(
            KTP::nik('3315131501901235')
                ->expectBirthDate('1990-01-15')
                ->expectGender(Gender::Male)
                ->expectProvince('Jawa Tengah')
                ->expectRegency(15)
                ->expectSubdistrict(13)
                ->isValid(),
        )->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});
