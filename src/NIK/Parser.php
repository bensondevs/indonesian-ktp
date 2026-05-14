<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\NIK;

use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\Support\TwoDigitYearExpander;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Stringable;

final class Parser
{
    /**
     * @param  bool  $usePivotForYearResolution  When true, resolve yy using {@see TwoDigitYearExpander::resolveUsingPivotYear}
     *                                           with {@see $evaluatedAt} as the pivot (same as legacy `asOf()` behaviour).
     */
    public static function parse(string $raw, CarbonInterface $evaluatedAt, bool $usePivotForYearResolution = false): Parsed
    {
        $nik = self::normalizedNikDigits($raw);
        if (blank($nik)) {
            return Parsed::invalid($raw);
        }

        $parts = self::structuralPartsFromNik($nik);

        if ($parts['serial'] < 1) {
            return Parsed::invalid($raw);
        }

        ['gender' => $gender, 'day' => $day] = self::genderAndDayFromDayField($parts['dayField']);

        if (! self::dayAndMonthArePlausible($day, $parts['month'])) {
            return Parsed::invalid($raw);
        }

        if ($usePivotForYearResolution) {
            return self::parsedWithPivotYear(
                $raw,
                $parts['districtCode'],
                $parts['yy'],
                $parts['month'],
                $day,
                $gender,
                $parts['serial'],
                $evaluatedAt,
            );
        }

        return self::parsedWithAmbiguousYears(
            $raw,
            $parts['districtCode'],
            $parts['yy'],
            $parts['month'],
            $day,
            $gender,
            $parts['serial'],
            $evaluatedAt,
        );
    }

    private static function normalizedNikDigits(string $raw): ?Stringable
    {
        $nik = str($raw)->replaceMatches('/\D/', '');

        return $nik->test('/^\d{16}$/') ? $nik : null;
    }

    /**
     * @return array{districtCode: string, dayField: int, month: int, yy: int, serial: int}
     */
    private static function structuralPartsFromNik(Stringable $nik): array
    {
        return [
            'districtCode' => collect(str_split($nik->substr(0, 6)->value(), 2))->implode('.'),
            'dayField' => (int) $nik->substr(6, 2)->value(),
            'month' => (int) $nik->substr(8, 2)->value(),
            'yy' => (int) $nik->substr(10, 2)->value(),
            'serial' => (int) $nik->substr(12, 4)->value(),
        ];
    }

    /**
     * @return array{gender: Gender, day: int}
     */
    private static function genderAndDayFromDayField(int $dayField): array
    {
        $gender = $dayField > 40 ? Gender::Female : Gender::Male;
        $day = $gender === Gender::Female ? $dayField - 40 : $dayField;

        return ['gender' => $gender, 'day' => $day];
    }

    private static function dayAndMonthArePlausible(int $day, int $month): bool
    {
        return $day >= 1 && $day <= 31 && $month >= 1 && $month <= 12;
    }

    private static function parsedWithPivotYear(
        string $raw,
        string $districtCode,
        int $yy,
        int $month,
        int $day,
        Gender $gender,
        int $serial,
        CarbonInterface $evaluatedAt,
    ): Parsed {
        $year = TwoDigitYearExpander::resolveUsingPivotYear($yy, $evaluatedAt);
        if (! checkdate($month, $day, $year)) {
            return Parsed::invalid($raw);
        }
        $birth = Carbon::createMidnightDate($year, $month, $day);

        return Parsed::valid($raw, [
            'districtCode' => $districtCode,
            'birthDate' => $birth,
            'birthDateCandidates' => [$birth],
            'gender' => $gender,
            'serial' => $serial,
        ]);
    }

    private static function parsedWithAmbiguousYears(
        string $raw,
        string $districtCode,
        int $yy,
        int $month,
        int $day,
        Gender $gender,
        int $serial,
        CarbonInterface $evaluatedAt,
    ): Parsed {
        $years = TwoDigitYearExpander::candidateBirthYearsForAmbiguousNik($yy, $month, $day, $evaluatedAt);

        if (blank($years)) {
            return Parsed::invalid($raw);
        }

        $sortedCandidates = collect($years)
            ->map(static fn (int $year): CarbonInterface => Carbon::createMidnightDate($year, $month, $day))
            ->sortBy(static fn (CarbonInterface $d): int => $d->getTimestamp())
            ->values();

        $candidates = $sortedCandidates->all();
        $birthDate = $sortedCandidates->count() === 1 ? $sortedCandidates->first() : null;

        return Parsed::valid($raw, [
            'districtCode' => $districtCode,
            'birthDate' => $birthDate,
            'birthDateCandidates' => $candidates,
            'gender' => $gender,
            'serial' => $serial,
        ]);
    }
}
