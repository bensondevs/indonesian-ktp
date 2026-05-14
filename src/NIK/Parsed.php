<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\NIK;

use Bensondevs\IndonesianKtp\Gender;
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
    ) {}

    public static function invalid(string $raw): self
    {
        return new self($raw, false, null, null, [], null, 0);
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

    public function age(CarbonInterface $asOf): ?int
    {
        if (! $this->structureValid || $this->birthDate === null) {
            return null;
        }

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
}
