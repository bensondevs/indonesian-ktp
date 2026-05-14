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
    private static ?FileRegionHierarchyLookup $fallbackLookup = null;

    public static function nik(string $raw): Query
    {
        $evaluatedAt = Carbon::now();

        if (function_exists('app')) {
            $app = app();
            if ($app instanceof Application && $app->bound(RegionHierarchyLookup::class)) {
                return new Query($raw, $evaluatedAt, false, $app->make(RegionHierarchyLookup::class));
            }
        }

        return new Query($raw, $evaluatedAt, false, self::fallbackLookup());
    }

    private static function fallbackLookup(): FileRegionHierarchyLookup
    {
        return self::$fallbackLookup ??= new FileRegionHierarchyLookup(
            dirname(__DIR__) . '/data/wilayah.php',
        );
    }
}
