<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\NIK\Query;
use Bensondevs\IndonesianKtp\Regions\Lookup\FileRegionHierarchyLookup;
use Carbon\Carbon;

test('expectAge rejects negative values', function () {
    $lookup = new FileRegionHierarchyLookup(dirname(__DIR__, 2).'/fixtures/wilayah-minimal.php');
    $query = new Query('3315130505984711', Carbon::parse('2026-01-01'), true, $lookup);

    expect(fn () => $query->expectAge(-1))
        ->toThrow(\InvalidArgumentException::class, 'Expected age cannot be negative.');
});

test('expectAtLeastYears rejects negative values', function () {
    $lookup = new FileRegionHierarchyLookup(dirname(__DIR__, 2).'/fixtures/wilayah-minimal.php');
    $query = new Query('3315130505984711', Carbon::parse('2026-01-01'), true, $lookup);

    expect(fn () => $query->expectAtLeastYears(-1))
        ->toThrow(\InvalidArgumentException::class, 'Minimum age cannot be negative.');
});
