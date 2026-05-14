<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp;

use Bensondevs\IndonesianKtp\NIK\Query;
use Bensondevs\IndonesianKtp\Regions\Lookup\FileRegionHierarchyLookup;
use Bensondevs\IndonesianKtp\Regions\Lookup\RegionHierarchyLookup;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;

final class KTP
{
    public static function nik(string $raw): Query
    {
        $evaluatedAt = Carbon::now();

        if (function_exists('app') && app() instanceof Application && app()->bound(RegionHierarchyLookup::class)) {
            return new Query($raw, $evaluatedAt, false, app(RegionHierarchyLookup::class));
        }

        $dataPath = dirname(__DIR__) . '/data/wilayah.php';

        return new Query($raw, $evaluatedAt, false, new FileRegionHierarchyLookup($dataPath));
    }
}
