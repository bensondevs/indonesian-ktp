<?php

declare(strict_types=1);

use Bensondevs\IndonesianKtp\Concerns\HasIndonesianKtp;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

test('README: HasIndonesianKtp — hasValidNik nikGenderIs nikProvinceIs', function () {
    $model = new class extends Model
    {
        use HasIndonesianKtp;

        protected $guarded = [];

        protected $table = 'users';
    };

    $model->forceFill([
        'nik' => '3315131501901235',
        'birthdate' => '1990-01-15',
        'gender' => 'male',
        'province' => 'JAWA TENGAH',
    ]);

    expect($model->hasValidNik())->toBeTrue()
        ->and($model->nikGenderIs($model->gender))->toBeTrue()
        ->and($model->nikProvinceIs($model->province))->toBeTrue();
});

test('README: HasIndonesianKtp — full attribute set and explicit matchers', function () {
    $model = new class extends Model
    {
        use HasIndonesianKtp;

        protected $guarded = [];

        protected $table = 'users';
    };

    $model->forceFill([
        'nik' => '3315131501901235',
        'birthdate' => '1990-01-15',
        'gender' => 'male',
        'province' => 'JAWA TENGAH',
        'regency' => 'KABUPATEN GROBOGAN',
        'subdistrict' => 'PURWODADI',
    ]);

    expect($model->hasValidNik())->toBeTrue()
        ->and($model->nikBirthdateIs($model->birthdate))->toBeTrue()
        ->and($model->nikGenderIs($model->gender))->toBeTrue()
        ->and($model->nikProvinceIs($model->province))->toBeTrue()
        ->and($model->nikRegencyIs($model->regency))->toBeTrue()
        ->and($model->nikSubdistrictIs($model->subdistrict))->toBeTrue();
});

test('README: HasIndonesianKtp — explicit birthdate ignores other stored attributes', function () {
    $model = new class extends Model
    {
        use HasIndonesianKtp;

        protected $guarded = [];

        protected $table = 'users';
    };

    $model->forceFill([
        'nik' => '3315131501901235',
        'nik_birth_date' => '2000-01-01',
        'birthdate' => '2000-01-01',
    ]);

    expect($model->nikBirthdateIs('1990-01-15'))->toBeTrue()
        ->and($model->nikBirthdateIs('2000-01-01'))->toBeFalse();
});

test('README: HasIndonesianKtp — alias methods smoke', function () {
    $model = new class extends Model
    {
        use HasIndonesianKtp;

        protected $guarded = [];

        protected $table = 'users';
    };

    $model->forceFill([
        'nik' => '3315131501901235',
        'birthdate' => '1990-01-15',
        'gender' => 'male',
        'province' => 'JAWA TENGAH',
        'regency' => 'KABUPATEN GROBOGAN',
        'kecamatan' => 'PURWODADI',
    ]);

    expect($model->hasValidIndonesianIdNumber())->toBeTrue()
        ->and($model->indonesianIdNumberBirthdateIs($model->birthdate))->toBeTrue()
        ->and($model->indonesianIdNumberKecamatanIs('PURWODADI'))->toBeTrue();
});

test('README: HasIndonesianKtp — getIndonesianKtpNikColumn', function () {
    $model = new class extends Model
    {
        use HasIndonesianKtp;

        protected $guarded = [];

        protected $table = 'users';

        protected function getIndonesianKtpNikColumn(): string
        {
            return 'identity_number';
        }
    };

    $model->forceFill([
        'identity_number' => '3315131501901235',
        'birthdate' => '1990-01-15',
        'gender' => 'male',
        'province' => 'JAWA TENGAH',
        'regency' => 'KABUPATEN GROBOGAN',
        'subdistrict' => 'PURWODADI',
    ]);

    expect($model->hasValidNik())->toBeTrue()
        ->and($model->nikBirthdateIs($model->birthdate))->toBeTrue();
});

test('README: HasIndonesianKtp — indonesianKtpReferenceDate fixed pivot for age', function () {
    $model = new class extends Model
    {
        use HasIndonesianKtp;

        protected $guarded = [];

        protected $table = 'users';

        protected function indonesianKtpReferenceDate(): ?CarbonInterface
        {
            return Carbon::parse('2026-01-01');
        }
    };

    $model->forceFill([
        'nik' => '3315131501901235',
        'age' => 35,
    ]);

    expect($model->ageFromNik())->toBe(35)
        ->and($model->ageFromIndonesianIdNumber())->toBe(35)
        ->and($model->isSeventeenOrOlderFromNik())->toBeTrue()
        ->and($model->isTwentyOneOrOlderFromNik())->toBeTrue()
        ->and($model->isAtLeastYearsFromNik(36))->toBeFalse()
        ->and($model->nikAgeIs(35))->toBeTrue()
        ->and($model->nikAgeIs(34))->toBeFalse();

    $model->age = 99;

    expect($model->nikAgeIs(35))->toBeTrue()
        ->and($model->nikAgeIs(34))->toBeFalse();
});

test('README: HasIndonesianKtp — ambiguous NIK without pivot on reference date', function () {
    Carbon::setTestNow('2026-09-01');

    try {
        $model = new class extends Model
        {
            use HasIndonesianKtp;

            protected $guarded = [];

            protected $table = 'users';
        };

        $model->forceFill(['nik' => '3315130109090002']);

        expect($model->ageFromNik())->toBeNull()
            ->and($model->isAtLeastYearsFromNik(17))->toBeTrue()
            ->and($model->isTwentyOneOrOlderFromNik())->toBeFalse();
    } finally {
        Carbon::setTestNow();
    }
});
