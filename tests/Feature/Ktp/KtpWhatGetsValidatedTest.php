<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\KTP;

test('README: What gets validated — wrong length', function () {
    expect(KTP::nik('123')->isValid())->toBeFalse();
});

test('README: What gets validated — unknown district (no expectations)', function () {
    expect(KTP::nik('9999991501900001')->isValid())->toBeFalse();
});
