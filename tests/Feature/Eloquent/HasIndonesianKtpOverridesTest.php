<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\Concerns\HasIndonesianKtp;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

test('README: HasIndonesianKtp — NIK accessor strips non-digits on default column', function () {
    $model = new class extends Model
    {
        use HasIndonesianKtp;

        protected $guarded = [];

        protected $table = 'users';

        protected function getNikAttribute(?string $value): ?string
        {
            return $value !== null ? preg_replace('/\D/', '', $value) : null;
        }
    };

    $model->forceFill([
        'nik' => '3315 1315 0190 1235',
        'birthdate' => '1990-01-15',
        'gender' => 'male',
        'province' => 'JAWA TENGAH',
    ]);

    expect($model->hasValidNik())->toBeTrue()
        ->and($model->nikBirthdateIs('1990-01-15'))->toBeTrue();
});

test('README: HasIndonesianKtp — nikBirthdateIs with date_of_birth column', function () {
    $model = new class extends Model
    {
        use HasIndonesianKtp;

        protected $guarded = [];

        protected $table = 'users';
    };

    $model->forceFill([
        'nik' => '3315131501901235',
        'date_of_birth' => '1990-01-15',
    ]);

    expect($model->nikBirthdateIs($model->date_of_birth))->toBeTrue();
});

test('README: HasIndonesianKtp — meta birthdate only when passed explicitly', function () {
    $model = new class extends Model
    {
        use HasIndonesianKtp;

        protected $guarded = [];

        protected $table = 'users';
    };

    $model->forceFill([
        'nik' => '3315131501901235',
        'meta_birthdate' => '1990-01-15',
        'birthdate' => '2000-01-01',
    ]);

    expect($model->nikBirthdateIs($model->meta_birthdate))->toBeTrue()
        ->and($model->nikBirthdateIs($model->birthdate))->toBeFalse();
});

test('README: HasIndonesianKtp — Applicant-style identity_number and explicit matchers', function () {
    $model = new class extends Model
    {
        use HasIndonesianKtp;

        protected $guarded = [];

        protected $table = 'applicants';

        protected function getIndonesianKtpNikColumn(): string
        {
            return 'identity_number';
        }
    };

    $model->forceFill([
        'identity_number' => '3315131501901235',
        'legacy_birthdate' => '1990-01-15',
        'sex_code' => 'male',
        'prov_name' => 'JAWA TENGAH',
        'kab_name' => 'KABUPATEN GROBOGAN',
        'kec_name' => 'PURWODADI',
    ]);

    expect($model->hasValidNik())->toBeTrue()
        ->and($model->nikBirthdateIs($model->legacy_birthdate))->toBeTrue()
        ->and($model->nikGenderIs($model->sex_code))->toBeTrue()
        ->and($model->nikProvinceIs($model->prov_name))->toBeTrue()
        ->and($model->nikRegencyIs($model->kab_name))->toBeTrue()
        ->and($model->nikSubdistrictIs($model->kec_name))->toBeTrue();
});

test('README: HasIndonesianKtp — indonesianKtpReferenceDate uses Carbon now pivot', function () {
    Carbon::setTestNow('2026-01-01');

    try {
        $model = new class extends Model
        {
            use HasIndonesianKtp;

            protected $guarded = [];

            protected $table = 'users';

            protected function indonesianKtpReferenceDate(): ?CarbonInterface
            {
                return Carbon::now();
            }
        };

        $model->forceFill([
            'nik' => '3315131501901235',
            'age' => 35,
        ]);

        expect($model->ageFromNik())->toBe(35)
            ->and($model->nikAgeIs(35))->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});
