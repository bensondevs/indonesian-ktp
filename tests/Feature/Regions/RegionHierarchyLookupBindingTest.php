<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\KTP;
use Bensondevs\IndonesianKtp\Regions\Lookup\FileRegionHierarchyLookup;
use Bensondevs\IndonesianKtp\Regions\Lookup\RegionHierarchyLookup;

test('README: Region hierarchy — rebinding lookup to empty districts invalidates known NIK', function () {
    $fixture = dirname(__DIR__, 2).'/fixtures/wilayah-no-districts.php';

    $this->app->singleton(RegionHierarchyLookup::class, fn (): FileRegionHierarchyLookup => new FileRegionHierarchyLookup($fixture));

    expect(KTP::nik('3315131501901235')->isValid())->toBeFalse();
});

test('README: Region hierarchy — rebinding lookup to minimal file keeps hierarchy valid', function () {
    $fixture = dirname(__DIR__, 2).'/fixtures/wilayah-minimal.php';

    $this->app->singleton(RegionHierarchyLookup::class, fn (): FileRegionHierarchyLookup => new FileRegionHierarchyLookup($fixture));

    expect(KTP::nik('3315131501901235')->isValid())->toBeTrue();
});
