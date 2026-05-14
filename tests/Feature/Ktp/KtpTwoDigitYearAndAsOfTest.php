<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\KTP;
use Carbon\Carbon;

test('README: Two-digit years — asOf pins resolved birth in parsed', function () {
    Carbon::setTestNow('2026-09-01');

    try {
        $ambiguousNik = '3315130109090002';

        expect(KTP::nik($ambiguousNik)->parsed()->birthDate())->toBeNull()
            ->and(count(KTP::nik($ambiguousNik)->parsed()->possibleBirthDates()))->toBe(2);

        expect(
            KTP::nik($ambiguousNik)
                ->asOf(Carbon::parse('2026-09-01'))
                ->parsed()
                ->birthDate(),
        )->not->toBeNull();
    } finally {
        Carbon::setTestNow();
    }
});

test('README: Two-digit years — matchBirthDate accepts either century without asOf', function () {
    Carbon::setTestNow('2026-09-01');

    try {
        $nik = '3315130109090002';

        expect(KTP::nik($nik)->matchBirthDate('1909-09-01'))->toBeTrue()
            ->and(KTP::nik($nik)->matchBirthDate('2009-09-01'))->toBeTrue()
            ->and(KTP::nik($nik)->matchBirthDate('2109-09-01'))->toBeFalse();

        $parsedNik = KTP::nik($nik)->parsed();
        expect($parsedNik->birthDate())->toBeNull()
            ->and($parsedNik->possibleBirthDates())->toHaveCount(2);
    } finally {
        Carbon::setTestNow();
    }
});

test('README: Two-digit years — asOf restores single possibleBirthDates entry', function () {
    Carbon::setTestNow('2026-09-01');

    try {
        $nik = '3315130109090002';
        $parsedNik = KTP::nik($nik)->asOf(Carbon::parse('2026-09-01'))->parsed();

        expect($parsedNik->birthDate())->not->toBeNull()
            ->and($parsedNik->possibleBirthDates())->toHaveCount(1);
    } finally {
        Carbon::setTestNow();
    }
});

test('README: Two-digit years — resolvedAge and matchAge with pivot', function () {
    $referenceDate = Carbon::parse('2026-01-01');
    $nikQuery = KTP::nik('3315131501901235')->asOf($referenceDate);

    expect($nikQuery->resolvedAge())->toBe(35)
        ->and($nikQuery->matchAge(35))->toBeTrue()
        ->and($nikQuery->matchAge(36))->toBeFalse()
        ->and($nikQuery->matchSeventeenOrOlder())->toBeTrue()
        ->and($nikQuery->matchTwentyOneOrOlder())->toBeTrue();
});

test('README: Two-digit years — ambiguous resolvedAge null and conservative minimum age', function () {
    Carbon::setTestNow('2026-09-01');

    try {
        $nik = '3315130109090002';
        $query = KTP::nik($nik);

        expect($query->resolvedAge())->toBeNull()
            ->and($query->matchAge(117))->toBeFalse()
            ->and($query->matchAtLeastYears(17))->toBeTrue()
            ->and($query->matchAtLeastYears(21))->toBeFalse();
    } finally {
        Carbon::setTestNow();
    }
});
