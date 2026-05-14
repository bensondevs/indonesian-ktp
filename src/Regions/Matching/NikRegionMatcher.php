<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\Regions\Matching;

use Bensondevs\IndonesianKtp\Regions\Data\RegionHierarchy;
use Bensondevs\IndonesianKtp\Regions\Lookup\RegionHierarchyLookup;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use function blank;
use function filled;
use function str;

final class NikRegionMatcher
{
    public function __construct(
        private readonly RegionHierarchyLookup $lookup,
    ) {}

    public function provinceMatches(?RegionHierarchy $hierarchy, int | string $expected): bool
    {
        if ($hierarchy === null || blank($hierarchy->province->code)) {
            return false;
        }

        $code = $hierarchy->province->code;
        $name = $hierarchy->province->name;

        if (is_int($expected) || (is_string($expected) && str((string) $expected)->trim()->test('/^\d+$/'))) {
            $want = str((string) (int) $expected)->padLeft(2, '0')->value();

            return $code === $want;
        }

        return $this->nameOrFuzzyProvince((string) $expected, $code, $name);
    }

    public function regencyMatches(?RegionHierarchy $hierarchy, int | string $expected): bool
    {
        if ($hierarchy === null || blank($hierarchy->regency->code)) {
            return false;
        }

        $code = $hierarchy->regency->code;
        $name = $hierarchy->regency->name;

        $normalized = $this->normalizeDottedPairOrQuad($expected, $hierarchy);

        if (filled($normalized)) {
            return $code === $normalized;
        }

        return $this->nameMatchesRegency((string) $expected, $name, $code, $hierarchy);
    }

    public function subdistrictMatches(?RegionHierarchy $hierarchy, int | string $expected): bool
    {
        if ($hierarchy === null || blank($hierarchy->district->code)) {
            return false;
        }

        $code = $hierarchy->district->code;
        $name = $hierarchy->district->name;

        $normalized = $this->normalizeDistrictInput($expected, $hierarchy);

        if (filled($normalized)) {
            return $code === $normalized;
        }

        return $this->nameMatchesDistrict((string) $expected, $name, $code, $hierarchy);
    }

    private function nameOrFuzzyProvince(string $expected, string $actualCode, string $actualName): bool
    {
        if ($this->upperNamesFuzzyMatch($expected, $actualName)) {
            return true;
        }

        $hit = $this->lookup->findProvinceByName($expected);

        return filled($hit) && $hit['code'] === $actualCode;
    }

    private function normalizeDottedPairOrQuad(int | string $expected, RegionHierarchy $hierarchy): ?string
    {
        if (is_int($expected)) {
            $provinceCode = $hierarchy->province->code;

            if (blank($provinceCode)) {
                return null;
            }

            return $provinceCode . '.' . str($expected)
                ->padLeft(2, '0')
                ->value();
        }

        $s = str($expected)->trim();
        if ($s->test('/^\d{2}\.\d{2}$/')) {
            return $s->value();
        }

        if ($s->length() === 4 && $s->test('/^\d{4}$/')) {
            return $s->substr(0, 2)->value() . '.' . $s->substr(2, 2)->value();
        }

        return null;
    }

    private function normalizeDistrictInput(int | string $expected, RegionHierarchy $hierarchy): ?string
    {
        $regencyCode = $hierarchy->regency->code;

        if (blank($regencyCode)) {
            return null;
        }

        if (is_int($expected)) {
            return $regencyCode . '.' . str((string) (int) $expected)->padLeft(2, '0')->value();
        }

        $t = str((string) $expected)->trim();
        if ($t->test('/^\d{1,2}$/')) {
            return $regencyCode . '.' . str((string) (int) $t->value())->padLeft(2, '0')->value();
        }

        if ($t->test('/^\d{2}\.\d{2}\.\d{2}$/')) {
            return $t->value();
        }

        if ($t->length() === 6 && $t->test('/^\d{6}$/')) {
            return $t->substr(0, 2)->value() . '.' . $t->substr(2, 2)->value() . '.' . $t->substr(4, 2)->value();
        }

        return null;
    }

    private function nameMatchesRegency(string $expected, string $actualName, string $actualCode, RegionHierarchy $hierarchy): bool
    {
        if ($this->upperNamesFuzzyMatch($expected, $actualName)) {
            return true;
        }

        $prov = $hierarchy->province->code;
        $hit = $this->lookup->findRegencyByName($expected, filled($prov) ? $prov : null);

        return filled($hit) && $hit['code'] === $actualCode;
    }

    private function nameMatchesDistrict(string $expected, string $actualName, string $actualCode, RegionHierarchy $hierarchy): bool
    {
        if ($this->upperNamesFuzzyMatch($expected, $actualName)) {
            return true;
        }

        $reg = $hierarchy->regency->code;
        $hit = $this->lookup->findDistrictByName($expected, filled($reg) ? $reg : null);

        return filled($hit) && $hit['code'] === $actualCode;
    }

    private function normalizedUpper(string $value): string
    {
        return str($value)->trim()->upper()->value();
    }

    private function upperNamesFuzzyMatch(string $expected, string $actualName): bool
    {
        $expectedUpper = $this->normalizedUpper($expected);
        $actualNameUpper = $this->normalizedUpper($actualName);

        if (blank($expectedUpper) || blank($actualNameUpper)) {
            return false;
        }

        return $expectedUpper === $actualNameUpper
            || str($actualNameUpper)->contains($expectedUpper)
            || str($expectedUpper)->contains($actualNameUpper);
    }

    public static function parseExpectedDate(CarbonInterface | string $value): Carbon
    {
        return Carbon::parse($value)->startOfDay();
    }
}
