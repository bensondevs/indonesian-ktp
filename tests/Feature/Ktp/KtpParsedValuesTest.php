<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\KTP;
use Carbon\Carbon;

test('README: Parsed values — snapshot for sample NIK with stable clock', function () {
    Carbon::setTestNow('2026-01-01');

    try {
        $parsed = KTP::nik('3315131501901235')->parsed();
        $asOf = Carbon::parse('2026-01-01');

        expect($parsed->raw())->toBe('3315131501901235')
            ->and($parsed->structureValid())->toBeTrue()
            ->and($parsed->districtCode())->toBe('33.15.13')
            ->and($parsed->provinceCode())->toBe('33')
            ->and($parsed->regencyCode())->toBe('33.15')
            ->and($parsed->province())->toBe('Jawa Tengah')
            ->and($parsed->regency())->toBe('Kabupaten Grobogan')
            ->and($parsed->district())->toBe('Purwodadi')
            ->and($parsed->city())->toBe('Kabupaten Grobogan')
            ->and($parsed->gender())->toBe(Gender::Male)
            ->and($parsed->serial())->toBe(1235)
            ->and($parsed->age($asOf))->toBe(35)
            ->and($parsed->isSeventeenOrOlder($asOf))->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});

test('README: Parsed values — possibleBirthDates is non-empty when structure valid', function () {
    Carbon::setTestNow('2026-01-01');

    try {
        $parsed = KTP::nik('3315131501901235')->parsed();

        expect($parsed->possibleBirthDates())->not->toBeEmpty();
    } finally {
        Carbon::setTestNow();
    }
});
