<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\Concerns;

use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\KTP;
use Bensondevs\IndonesianKtp\NIK\Query;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use function blank;
use function filled;

/**
 * @mixin Model
 */
trait HasIndonesianKtp
{
    /**
     * Eloquent attribute name that stores the NIK (non-digits are stripped after read).
     */
    protected function getIndonesianKtpNikColumn(): string
    {
        return 'nik';
    }

    public function hasValidNik(): bool
    {
        $nik = $this->normalizedIndonesianKtpNik();

        if (blank($nik)) {
            return false;
        }

        return $this->nikQueryForModel($nik)->isValid();
    }

    public function hasValidIndonesianIdNumber(): bool
    {
        return $this->hasValidNik();
    }

    public function nikBirthdateIs(mixed $birth): bool
    {
        $nik = $this->normalizedIndonesianKtpNik();

        if (blank($nik) || blank($birth)) {
            return false;
        }

        return $this->nikQueryForModel($nik)->matchBirthDate($birth);
    }

    public function indonesianIdNumberBirthdateIs(mixed $birth): bool
    {
        return $this->nikBirthdateIs($birth);
    }

    public function nikGenderIs(Gender|string $gender): bool
    {
        $nik = $this->normalizedIndonesianKtpNik();

        if (blank($nik)) {
            return false;
        }

        if ($gender instanceof Gender) {
            return $this->nikQueryForModel($nik)->matchGender($gender);
        }

        if (blank($gender)) {
            return false;
        }

        return $this->nikQueryForModel($nik)->matchGender((string) $gender);
    }

    public function indonesianIdNumberGenderIs(Gender|string $gender): bool
    {
        return $this->nikGenderIs($gender);
    }

    public function nikProvinceIs(mixed $expected): bool
    {
        return $this->indonesianKtpMatchesExpectation(
            $expected,
            fn (Query $query, mixed $value): Query => $query->expectProvince($value),
        );
    }

    public function indonesianIdNumberProvinceIs(mixed $expected): bool
    {
        return $this->nikProvinceIs($expected);
    }

    public function nikRegencyIs(mixed $expected): bool
    {
        return $this->indonesianKtpMatchesExpectation(
            $expected,
            fn (Query $query, mixed $value): Query => $query->expectRegency($value),
        );
    }

    public function indonesianIdNumberRegencyIs(mixed $expected): bool
    {
        return $this->nikRegencyIs($expected);
    }

    public function nikKabupatenIs(mixed $expected): bool
    {
        return $this->nikRegencyIs($expected);
    }

    public function indonesianIdNumberKabupatenIs(mixed $expected): bool
    {
        return $this->nikKabupatenIs($expected);
    }

    public function nikCityIs(mixed $expected): bool
    {
        return $this->nikRegencyIs($expected);
    }

    public function indonesianIdNumberCityIs(mixed $expected): bool
    {
        return $this->nikCityIs($expected);
    }

    public function nikDistrictIs(mixed $expected): bool
    {
        return $this->nikRegencyIs($expected);
    }

    public function indonesianIdNumberDistrictIs(mixed $expected): bool
    {
        return $this->nikDistrictIs($expected);
    }

    public function nikSubdistrictIs(mixed $expected): bool
    {
        return $this->indonesianKtpMatchesExpectation(
            $expected,
            fn (Query $query, mixed $value): Query => $query->expectSubdistrict($value),
        );
    }

    public function indonesianIdNumberSubdistrictIs(mixed $expected): bool
    {
        return $this->nikSubdistrictIs($expected);
    }

    public function nikKecamatanIs(mixed $expected): bool
    {
        return $this->nikSubdistrictIs($expected);
    }

    public function indonesianIdNumberKecamatanIs(mixed $expected): bool
    {
        return $this->nikKecamatanIs($expected);
    }

    public function nikAgeIs(int $age): bool
    {
        if ($age < 0) {
            return false;
        }

        $nik = $this->normalizedIndonesianKtpNik();

        if (blank($nik)) {
            return false;
        }

        return $this->nikQueryForModel($nik)->matchAge($age);
    }

    public function indonesianIdNumberAgeIs(int $age): bool
    {
        return $this->nikAgeIs($age);
    }

    public function ageFromNik(): ?int
    {
        $nik = $this->normalizedIndonesianKtpNik();

        if (blank($nik)) {
            return null;
        }

        return $this->nikQueryForModel($nik)->resolvedAge();
    }

    public function ageFromIndonesianIdNumber(): ?int
    {
        return $this->ageFromNik();
    }

    public function isSeventeenOrOlderFromNik(): bool
    {
        return $this->isAtLeastYearsFromNik(17);
    }

    public function isSeventeenOrOlderFromIndonesianIdNumber(): bool
    {
        return $this->isSeventeenOrOlderFromNik();
    }

    public function isTwentyOneOrOlderFromNik(): bool
    {
        return $this->isAtLeastYearsFromNik(21);
    }

    public function isTwentyOneOrOlderFromIndonesianIdNumber(): bool
    {
        return $this->isTwentyOneOrOlderFromNik();
    }

    public function isAtLeastYearsFromNik(int $minYears): bool
    {
        if ($minYears < 0) {
            return false;
        }

        $nik = $this->normalizedIndonesianKtpNik();

        if (blank($nik)) {
            return false;
        }

        return $this->nikQueryForModel($nik)->matchAtLeastYears($minYears);
    }

    public function isAtLeastYearsFromIndonesianIdNumber(int $minYears): bool
    {
        return $this->isAtLeastYearsFromNik($minYears);
    }

    /**
     * When non-null, two-digit birth years — and {@see Query::resolvedAge()}, minimum-age checks, and {@see Query::matchAge()} — use {@see Query::asOf()} with this instant.
     * Default null uses ambiguous resolution (KTP holder age bounds at query construction time).
     * Return {@see \Carbon\Carbon::now()} to restore the previous package default of pivoting on “now”.
     */
    protected function indonesianKtpReferenceDate(): ?CarbonInterface
    {
        return null;
    }

    private function nikQueryForModel(string $nik): Query
    {
        $nikQuery = KTP::nik($nik);
        $pivot = $this->indonesianKtpReferenceDate();

        return filled($pivot) ? $nikQuery->asOf($pivot) : $nikQuery;
    }

    private function normalizedIndonesianKtpNik(): ?string
    {
        $column = $this->getIndonesianKtpNikColumn();
        $attributeValue = $this->getAttribute($column);

        if (blank($attributeValue)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $attributeValue);

        return filled($digits) ? $digits : null;
    }

    /**
     * @param  callable(Query, mixed): Query  $applyExpectation
     */
    private function indonesianKtpMatchesExpectation(mixed $value, callable $applyExpectation): bool
    {
        $nik = $this->normalizedIndonesianKtpNik();

        if (blank($nik) || blank($value)) {
            return false;
        }

        $nikQuery = $this->nikQueryForModel($nik);

        return $applyExpectation($nikQuery, $value)->isValid();
    }
}
