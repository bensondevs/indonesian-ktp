<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\KTP;
use Carbon\Carbon;

test('README: Region inputs — expectProvince code and name', function () {
    Carbon::setTestNow('2026-01-01');

    try {
        $sampleNik = '3315131501901235';

        expect(KTP::nik($sampleNik)->expectProvince(33)->isValid())->toBeTrue()
            ->and(KTP::nik($sampleNik)->expectProvince('JAWA TENGAH')->isValid())->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});

test('README: Region inputs — expectRegency alone', function () {
    Carbon::setTestNow('2026-01-01');

    try {
        expect(
            KTP::nik('3315131501901235')
                ->expectRegency(15)
                ->isValid(),
        )->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});

test('README: Region inputs — expectRegency and expectSubdistrict', function () {
    Carbon::setTestNow('2026-01-01');

    try {
        expect(
            KTP::nik('3315131501901235')
                ->expectRegency(15)
                ->expectSubdistrict(13)
                ->isValid(),
        )->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});

test('README: Region inputs — unknown district', function () {
    expect(KTP::nik('9999991501900001')->isValid())->toBeFalse();
});
