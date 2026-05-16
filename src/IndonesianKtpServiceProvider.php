<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp;

use Bensondevs\IndonesianKtp\Regions\Lookup\FileRegionHierarchyLookup;
use Bensondevs\IndonesianKtp\Regions\Lookup\RegionHierarchyLookup;
use Bensondevs\IndonesianKtp\Rules\KtpNik;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Validator as ValidatorInstance;

class IndonesianKtpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RegionHierarchyLookup::class, function (): FileRegionHierarchyLookup {
            $path = dirname(__DIR__) . '/data/wilayah.php';

            return new FileRegionHierarchyLookup($path);
        });
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(dirname(__DIR__) . '/lang', 'indonesian-ktp');

        $extension = static fn (string $attribute, mixed $value, array $parameters, ValidatorInstance $validator): bool => KtpNik::passes($value);

        Validator::extend('ktp-nik', $extension, 'indonesian-ktp::validation.ktp_nik');
        Validator::extend('ktp_nik', $extension, 'indonesian-ktp::validation.ktp_nik');

        $replacer = static fn (string $message, string $attribute, string $rule, array $parameters, ValidatorInstance $validator): string => __(
            'indonesian-ktp::validation.ktp_nik',
            ['attribute' => $validator->getDisplayableAttribute($attribute)],
        );

        Validator::replacer('ktp-nik', $replacer);
        Validator::replacer('ktp_nik', $replacer);
    }
}
