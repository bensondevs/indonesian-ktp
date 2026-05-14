<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\Regions\Lookup\FileRegionHierarchyLookup;
use Bensondevs\IndonesianKtp\Regions\Matching\NikRegionMatcher;

test('NikRegionMatcher matches province regency and subdistrict by code and name', function () {
    $fixture = dirname(__DIR__, 3).'/fixtures/wilayah-minimal.php';
    $lookup = new FileRegionHierarchyLookup($fixture);
    $matcher = new NikRegionMatcher($lookup);
    $hierarchy = $lookup->hierarchy('33.15.13');

    expect($hierarchy)->not->toBeNull()
        ->and($matcher->provinceMatches($hierarchy, 33))->toBeTrue()
        ->and($matcher->provinceMatches($hierarchy, 'jawa tengah'))->toBeTrue()
        ->and($matcher->regencyMatches($hierarchy, 15))->toBeTrue()
        ->and($matcher->regencyMatches($hierarchy, 'Kabupaten Grobogan'))->toBeTrue()
        ->and($matcher->subdistrictMatches($hierarchy, 13))->toBeTrue()
        ->and($matcher->subdistrictMatches($hierarchy, 'Purwodadi'))->toBeTrue();
});
