<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\NIK;

use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\Regions\Data\RegionHierarchy;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final readonly class Parsed
{
    /**
     * @param  list<CarbonInterface>  $birthDateCandidates
     */
    private function __construct(
        private string $raw,
        private bool $structureValid,
        private ?string $districtCode,
        private ?CarbonInterface $birthDate,
        private array $birthDateCandidates,
        private ?Gender $gender,
        private int $serial,
        private ?RegionHierarchy $regionHierarchy,
    ) {}

    public static function invalid(string $raw): self
    {
        return new self($raw, false, null, null, [], null, 0, null);
    }

    /**
     * @param  array{
     *     districtCode: string,
     *     birthDate: ?CarbonInterface,
     *     birthDateCandidates: list<CarbonInterface>,
     *     gender: Gender,
     *     serial: int
     * }  $parts
     */
    public static function valid(string $raw, array $parts): self
    {
        return new self(
            $raw,
            true,
            $parts['districtCode'],
            $parts['birthDate'],
            $parts['birthDateCandidates'],
            $parts['gender'],
            $parts['serial'],
            null,
        );
    }

    /**
     * Copy with wilayah display names resolved from {@see RegionHierarchy} (used by {@see Query::parsed()}).
     */
    public function withRegionHierarchy(?RegionHierarchy $regionHierarchy): self
    {
        return new self(
            $this->raw,
            $this->structureValid,
            $this->districtCode,
            $this->birthDate,
            $this->birthDateCandidates,
            $this->gender,
            $this->serial,
            $regionHierarchy,
        );
    }

    public function raw(): string
    {
        return $this->raw;
    }

    public function structureValid(): bool
    {
        return $this->structureValid;
    }

    public function districtCode(): ?string
    {
        return $this->districtCode;
    }

    /**
     * Province display name when a {@see RegionHierarchy} is attached (e.g. via {@see Query::parsed()}); otherwise {@code null}.
     */
    public function province(): ?string
    {
        return $this->regionHierarchy?->province->name;
    }

    /**
     * Regency or city display name when a hierarchy is attached; otherwise {@code null}.
     */
    public function regency(): ?string
    {
        return $this->regionHierarchy?->regency->name;
    }

    /**
     * Kecamatan (subdistrict) display name when a hierarchy is attached; otherwise {@code null}.
     */
    public function district(): ?string
    {
        return $this->regionHierarchy?->district->name;
    }

    /**
     * Wilayah province code from the NIK (first segment of {@see districtCode()}, e.g. {@code 33}).
     */
    public function provinceCode(): ?string
    {
        $segments = $this->regionCodeSegments();

        return $segments[0] ?? null;
    }

    /**
     * Wilayah regency code from the NIK (first two segments, e.g. {@code 33.15}).
     */
    public function regencyCode(): ?string
    {
        $segments = $this->regionCodeSegments();
        if ($segments === null) {
            return null;
        }

        return $segments[0] . '.' . $segments[1];
    }

    public function provinsi(): ?string
    {
        return $this->province();
    }

    public function kabupaten(): ?string
    {
        return $this->regency();
    }

    public function kota(): ?string
    {
        return $this->regency();
    }

    public function city(): ?string
    {
        return $this->regency();
    }

    public function kecamatan(): ?string
    {
        return $this->district();
    }

    public function birthDate(): ?CarbonInterface
    {
        return $this->birthDate;
    }

    public function gender(): ?Gender
    {
        return $this->gender;
    }

    public function serial(): int
    {
        return $this->serial;
    }

    /**
     * @return list<CarbonInterface>
     */
    public function possibleBirthDates(): array
    {
        return $this->birthDateCandidates;
    }

    public function birthDateEqualsDate(CarbonInterface | string $other): bool
    {
        if (! $this->structureValid || collect($this->birthDateCandidates)->isEmpty()) {
            return false;
        }

        $comparisonDate = Carbon::parse($other)->startOfDay();

        return collect($this->birthDateCandidates)->contains(
            static fn (CarbonInterface $candidate): bool => $candidate->isSameDay($comparisonDate),
        );
    }

    public function genderEquals(Gender | string $other): bool
    {
        if (! $this->structureValid || $this->gender === null) {
            return false;
        }

        $resolvedGender = Gender::tryFromMixed($other);

        return $resolvedGender !== null && $this->gender === $resolvedGender;
    }

    /**
     * Completed years since {@see birthDate()}. When {@code $asOf} is omitted, uses {@see Carbon::now()} (use {@see Carbon::setTestNow()} in tests for a fixed clock).
     */
    public function age(?CarbonInterface $asOf = null): ?int
    {
        if (! $this->structureValid || $this->birthDate === null) {
            return null;
        }

        $asOf ??= Carbon::now();

        return self::completedYearsSinceBirth($this->birthDate, $asOf);
    }

    public function isAtLeastYears(int $minYears, CarbonInterface $asOf): bool
    {
        if (! $this->structureValid || $this->birthDateCandidates === []) {
            return false;
        }

        if ($minYears < 0) {
            return false;
        }

        return collect($this->birthDateCandidates)->every(
            static fn (CarbonInterface $candidate): bool => self::candidateIsAtLeastYearsOld($candidate, $asOf, $minYears),
        );
    }

    public function isSeventeenOrOlder(CarbonInterface $asOf): bool
    {
        return $this->isAtLeastYears(17, $asOf);
    }

    public function isTwentyOneOrOlder(CarbonInterface $asOf): bool
    {
        return $this->isAtLeastYears(21, $asOf);
    }

    public function ageEquals(int $years, CarbonInterface $asOf): bool
    {
        $resolved = $this->age($asOf);

        return $resolved !== null && $resolved === $years;
    }

    /**
     * Same calendar rule as {@see \Bensondevs\IndonesianKtp\Support\TwoDigitYearExpander::birthDateMeetsKtpHolderAgeBounds} for the 17-year boundary: not {@code $asOf->lt($birth->copy()->addYears($minYears))}.
     */
    private static function candidateIsAtLeastYearsOld(CarbonInterface $candidate, CarbonInterface $asOf, int $minYears): bool
    {
        return ! $asOf->lt($candidate->copy()->addYears($minYears));
    }

    private static function completedYearsSinceBirth(CarbonInterface $birth, CarbonInterface $asOf): int
    {
        if ($asOf->lt($birth)) {
            return 0;
        }

        for ($n = 0; $n < 150; $n++) {
            if ($asOf->lt($birth->copy()->addYears($n + 1))) {
                return $n;
            }
        }

        return 149;
    }

    /**
     * @return array{0: string, 1: string, 2: string}|null
     */
    private function regionCodeSegments(): ?array
    {
        if (! $this->structureValid || $this->districtCode === null || $this->districtCode === '') {
            return null;
        }

        $parts = explode('.', $this->districtCode);

        if (count($parts) !== 3) {
            return null;
        }

        return [$parts[0], $parts[1], $parts[2]];
    }
}
