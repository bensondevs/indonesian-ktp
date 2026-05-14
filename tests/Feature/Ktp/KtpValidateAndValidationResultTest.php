<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\KTP;
use Carbon\Carbon;

test('README: validate — tri-state when expectations unset', function () {
    $validationResult = KTP::nik('3315131501901235')
        ->asOf(Carbon::parse('2026-01-01'))
        ->validate();

    expect($validationResult->hasValidStructure())->toBeTrue()
        ->and($validationResult->hasValidRegionHierarchy())->toBeTrue()
        ->and($validationResult->hasValidGender())->toBeNull()
        ->and($validationResult->hasValidBirthDate())->toBeNull()
        ->and($validationResult->hasValidProvince())->toBeNull()
        ->and($validationResult->hasValidRegency())->toBeNull()
        ->and($validationResult->hasValidSubdistrict())->toBeNull()
        ->and($validationResult->hasValidAge())->toBeNull()
        ->and($validationResult->hasValidMinimumAge())->toBeNull();
});

test('README: validate — birth mismatch', function () {
    $validationResult = KTP::nik('3315131501901235')
        ->asOf(Carbon::parse('2026-01-01'))
        ->birthDate('1990-01-01')
        ->validate();

    expect($validationResult->hasValidBirthDate())->toBeFalse();
});

test('README: validate — isFullyValid matches query isValid', function () {
    $query = KTP::nik('3315131501901235')->asOf(Carbon::parse('2026-01-01'));
    $validationResult = $query->validate();

    expect($validationResult->isFullyValid())->toBe($query->isValid());
});

test('README: ValidationResult alias methods mirror regency and subdistrict', function () {
    $validationResult = KTP::nik('3315131501901235')
        ->asOf(Carbon::parse('2026-01-01'))
        ->validate();

    expect($validationResult->hasValidKabupaten())->toBe($validationResult->hasValidRegency())
        ->and($validationResult->hasValidCity())->toBe($validationResult->hasValidRegency())
        ->and($validationResult->hasValidKecamatan())->toBe($validationResult->hasValidSubdistrict());
});

test('README: validate — age expectations tri-state and mismatch', function () {
    $referenceDate = Carbon::parse('2026-01-01');
    $base = KTP::nik('3315131501901235')->asOf($referenceDate);

    expect($base->validate()->hasValidAge())->toBeNull()
        ->and($base->validate()->hasValidMinimumAge())->toBeNull();

    expect($base->expectAge(35)->validate()->hasValidAge())->toBeTrue()
        ->and($base->expectAge(34)->validate()->hasValidAge())->toBeFalse();

    expect($base->expectSeventeenOrOlder()->validate()->hasValidMinimumAge())->toBeTrue()
        ->and($base->expectTwentyOneOrOlder()->validate()->hasValidMinimumAge())->toBeTrue()
        ->and($base->expectAtLeastYears(36)->validate()->hasValidMinimumAge())->toBeFalse();
});

test('README: validate — ambiguous NIK expectAge false and minimum-age conservative', function () {
    Carbon::setTestNow('2026-09-01');

    try {
        $nik = '3315130109090002';
        $query = KTP::nik($nik);

        expect($query->expectAge(27)->validate()->hasValidAge())->toBeFalse();

        expect($query->expectTwentyOneOrOlder()->validate()->hasValidMinimumAge())->toBeFalse()
            ->and($query->expectSeventeenOrOlder()->validate()->hasValidMinimumAge())->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});
