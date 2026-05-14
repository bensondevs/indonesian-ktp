<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\Regions\Lookup;

use Bensondevs\IndonesianKtp\Regions\Data\RegionHierarchy;

/**
 * Province / regency / district hierarchy for NIK district codes — no database required.
 */
interface RegionHierarchyLookup
{
    public function hierarchy(string $districtCode): ?RegionHierarchy;

    /**
     * @return array{code: string, name: string}|null
     */
    public function findProvinceByName(string $term): ?array;

    /**
     * @return array{code: string, name: string}|null
     */
    public function findRegencyByName(string $term, ?string $provinceCode = null): ?array;

    /**
     * @return array{code: string, name: string}|null
     */
    public function findDistrictByName(string $term, ?string $regencyCode = null): ?array;
}
