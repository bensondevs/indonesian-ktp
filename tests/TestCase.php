<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\Tests;

use Bensondevs\IndonesianKtp\IndonesianKtpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return [
            IndonesianKtpServiceProvider::class,
        ];
    }
}
