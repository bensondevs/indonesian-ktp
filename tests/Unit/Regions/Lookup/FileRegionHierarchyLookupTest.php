<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\Regions\Lookup\FileRegionHierarchyLookup;

test('hierarchy resolves known district from fixture file', function () {
    $fixture = dirname(__DIR__, 3).'/fixtures/wilayah-minimal.php';
    $lookup = new FileRegionHierarchyLookup($fixture);

    $hierarchy = $lookup->hierarchy('33.15.13');

    expect($hierarchy)->not->toBeNull()
        ->and($hierarchy->province->code)->toBe('33')
        ->and($hierarchy->regency->code)->toBe('33.15')
        ->and($hierarchy->district->code)->toBe('33.15.13');
});

test('hierarchy returns null for unknown district code', function () {
    $fixture = dirname(__DIR__, 3).'/fixtures/wilayah-minimal.php';
    $lookup = new FileRegionHierarchyLookup($fixture);

    expect($lookup->hierarchy('99.99.99'))->toBeNull();
});
