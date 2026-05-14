<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class TwoDigitYearExpander
{
    public static function clampTwoDigitYear(int $twoDigitYear): int
    {
        return max(0, min(99, $twoDigitYear));
    }

    /**
     * Resolve a two-digit NIK birth year to a single full year using a pivot date (legacy behaviour).
     */
    public static function resolveUsingPivotYear(int $twoDigitYear, CarbonInterface $pivot): int
    {
        $yy = self::clampTwoDigitYear($twoDigitYear);
        $refYear = $pivot->year;
        $century = intdiv($refYear, 100) * 100;
        $candidate = $century + $yy;

        while ($candidate > $refYear + 1) {
            $candidate -= 100;
        }

        while ($candidate < $refYear - 120) {
            $candidate += 100;
        }

        return $candidate;
    }

    public static function birthDateMeetsKtpHolderAgeBounds(CarbonInterface $birthMidnight, CarbonInterface $asOf): bool
    {
        if ($asOf->lt($birthMidnight)) {
            return false;
        }

        $tooYoung = $asOf->lt($birthMidnight->copy()->addYears(17));
        $tooOld = $asOf->gt($birthMidnight->copy()->addYears(120));

        return ! $tooYoung && ! $tooOld;
    }

    /**
     * Full birth years matching the NIK yy fragment and calendar (month, day), where the implied
     * birth date is plausible for a KTP holder (17–120 years inclusive relative to {@see $evaluatedAt}).
     *
     * @return list<int>
     */
    public static function candidateBirthYearsForAmbiguousNik(
        int $twoDigitYear,
        int $month,
        int $day,
        CarbonInterface $evaluatedAt,
    ): array {
        $yy = self::clampTwoDigitYear($twoDigitYear);
        $years = [];

        $minYear = $evaluatedAt->year - 121;
        $maxYear = $evaluatedAt->year;

        for ($year = $minYear; $year <= $maxYear; $year++) {
            if ($year % 100 !== $yy) {
                continue;
            }
            if (! checkdate($month, $day, $year)) {
                continue;
            }
            $birth = Carbon::createMidnightDate($year, $month, $day);
            if (self::birthDateMeetsKtpHolderAgeBounds($birth, $evaluatedAt)) {
                $years[] = $year;
            }
        }

        return $years;
    }
}
