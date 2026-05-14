<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\NIK\Parser;
use Bensondevs\IndonesianKtp\Regions\Lookup\FileRegionHierarchyLookup;
use Carbon\Carbon;

test('parses valid male NIK with known region hierarchy and birth date using pivot', function () {
    $ref = Carbon::parse('2026-01-15');
    $parsed = Parser::parse('3315131501901235', $ref, true);

    expect($parsed->structureValid())->toBeTrue()
        ->and($parsed->districtCode())->toBe('33.15.13')
        ->and($parsed->provinceCode())->toBe('33')
        ->and($parsed->regencyCode())->toBe('33.15')
        ->and($parsed->province())->toBeNull()
        ->and($parsed->provinsi())->toBeNull()
        ->and($parsed->regency())->toBeNull()
        ->and($parsed->kabupaten())->toBeNull()
        ->and($parsed->kota())->toBeNull()
        ->and($parsed->city())->toBeNull()
        ->and($parsed->district())->toBeNull()
        ->and($parsed->kecamatan())->toBeNull()
        ->and($parsed->gender())->toBe(Gender::Male)
        ->and($parsed->birthDate()?->toDateString())->toBe('1990-01-15')
        ->and($parsed->possibleBirthDates())->toHaveCount(1)
        ->and($parsed->serial())->toBe(1235)
        ->and($parsed->age($ref))->toBe(36)
        ->and($parsed->isSeventeenOrOlder($ref))->toBeTrue()
        ->and($parsed->isTwentyOneOrOlder($ref))->toBeTrue();
});

test('wilayah display names when hierarchy attached via withRegionHierarchy', function () {
    $fixture = dirname(__DIR__, 2).'/fixtures/wilayah-minimal.php';
    $lookup = new FileRegionHierarchyLookup($fixture);
    $hierarchy = $lookup->hierarchy('33.15.13');
    $parsed = Parser::parse('3315131501901235', Carbon::parse('2026-01-15'), true)
        ->withRegionHierarchy($hierarchy);

    expect($hierarchy)->not->toBeNull()
        ->and($parsed->province())->toBe('Jawa Tengah')
        ->and($parsed->provinsi())->toBe('Jawa Tengah')
        ->and($parsed->regency())->toBe('Kabupaten Grobogan')
        ->and($parsed->kabupaten())->toBe('Kabupaten Grobogan')
        ->and($parsed->kota())->toBe('Kabupaten Grobogan')
        ->and($parsed->city())->toBe('Kabupaten Grobogan')
        ->and($parsed->district())->toBe('Purwodadi')
        ->and($parsed->kecamatan())->toBe('Purwodadi')
        ->and($parsed->provinceCode())->toBe('33')
        ->and($parsed->regencyCode())->toBe('33.15')
        ->and($parsed->districtCode())->toBe('33.15.13');
});

test('completed age respects birthday boundary', function () {
    $parsed = Parser::parse('3315131501901235', Carbon::parse('2026-01-01'), true);

    expect($parsed->age(Carbon::parse('2026-01-14')))->toBe(35)
        ->and($parsed->age(Carbon::parse('2026-01-15')))->toBe(36)
        ->and($parsed->age(Carbon::parse('2026-01-15 23:59:59')))->toBe(36);
});

test('ambiguous mode age is null but isAtLeastYears uses every candidate', function () {
    $evaluatedAt = Carbon::parse('2026-09-01');
    $parsed = Parser::parse('3315130109090002', $evaluatedAt, false);

    expect($parsed->age($evaluatedAt))->toBeNull()
        ->and($parsed->isAtLeastYears(17, $evaluatedAt))->toBeTrue()
        ->and($parsed->isAtLeastYears(21, $evaluatedAt))->toBeFalse();
});

test('parses female day offset using pivot', function () {
    $ref = Carbon::parse('2026-01-15');
    $parsed = Parser::parse('3315134505980003', $ref, true);

    expect($parsed->structureValid())->toBeTrue()
        ->and($parsed->gender())->toBe(Gender::Female)
        ->and($parsed->birthDate()?->toDateString())->toBe('1998-05-05');
});

test('region getters are null when structure invalid', function () {
    $parsed = Parser::parse('1234', Carbon::now(), false);

    expect($parsed->province())->toBeNull()
        ->and($parsed->regency())->toBeNull()
        ->and($parsed->district())->toBeNull()
        ->and($parsed->city())->toBeNull()
        ->and($parsed->provinceCode())->toBeNull()
        ->and($parsed->regencyCode())->toBeNull()
        ->and($parsed->districtCode())->toBeNull();
});

test('age() without asOf uses Carbon now', function () {
    Carbon::setTestNow('2026-01-15');

    try {
        $parsed = Parser::parse('3315131501901235', Carbon::parse('2026-01-15'), true);

        expect($parsed->age())->toBe(36);
    } finally {
        Carbon::setTestNow();
    }
});

test('rejects invalid length', function () {
    expect(Parser::parse('1234', Carbon::now(), false)->structureValid())->toBeFalse();
});

test('rejects non digit characters after strip', function () {
    expect(Parser::parse('331513150190123a', Carbon::now(), false)->structureValid())->toBeFalse();
});

test('rejects serial 0000', function () {
    expect(Parser::parse('3315131501900000', Carbon::now(), false)->structureValid())->toBeFalse();
});

test('rejects impossible calendar date', function () {
    expect(Parser::parse('3315133131980001', Carbon::now(), false)->structureValid())->toBeFalse();
});

test('ambiguous mode yields multiple possible birth dates when pivot is off', function () {
    $evaluatedAt = Carbon::parse('2026-09-01');
    $parsed = Parser::parse('3315130109090002', $evaluatedAt, false);

    expect($parsed->structureValid())->toBeTrue()
        ->and($parsed->birthDate())->toBeNull()
        ->and($parsed->possibleBirthDates())->toHaveCount(2)
        ->and($parsed->birthDateEqualsDate('1909-09-01'))->toBeTrue()
        ->and($parsed->birthDateEqualsDate('2009-09-01'))->toBeTrue()
        ->and($parsed->birthDateEqualsDate('1999-09-01'))->toBeFalse();
});
