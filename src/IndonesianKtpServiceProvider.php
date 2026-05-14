<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp;

use Bensondevs\IndonesianKtp\Regions\Lookup\FileRegionHierarchyLookup;
use Bensondevs\IndonesianKtp\Regions\Lookup\RegionHierarchyLookup;
use Illuminate\Support\ServiceProvider;

class IndonesianKtpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RegionHierarchyLookup::class, function (): FileRegionHierarchyLookup {
            $path = dirname(__DIR__) . '/data/wilayah.php';

            return new FileRegionHierarchyLookup($path);
        });
    }
}
