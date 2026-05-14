<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\Regions\Lookup;

use Bensondevs\IndonesianKtp\Regions\Data\RegionHierarchy;
use Bensondevs\IndonesianKtp\Regions\Data\RegionUnit;
use function blank;
use function filled;

/**
 * Loads compiled PHP data from {@see https://github.com/cahyadsn/wilayah} (MIT).
 */
final class FileRegionHierarchyLookup implements RegionHierarchyLookup
{
    /** @var array<string, array{code: string, name: string}>|null */
    private ?array $provinceByCode = null;

    /** @var array<string, array{code: string, province_code: string, name: string}>|null */
    private ?array $regencyByCode = null;

    /** @var array<string, array{code: string, regency_code: string, name: string}>|null */
    private ?array $districtByCode = null;

    /** @var array<string, string>|null  Normalized name => province code */
    private ?array $provinceCodeByNormalizedName = null;

    /** @var array<string, list<string>>|null  Normalized name => list of regency codes (multiple if names repeat) */
    private ?array $regencyCodesByNormalizedName = null;

    /** @var array<string, list<string>>|null  Normalized name => list of district codes */
    private ?array $districtCodesByNormalizedName = null;

    public function __construct(
        private readonly string $dataFilePath,
    ) {}

    public function hierarchy(string $districtCode): ?RegionHierarchy
    {
        $this->ensureLoaded();

        $district = $this->districtByCode[$districtCode] ?? null;
        if ($district === null) {
            return null;
        }

        $regency = $this->regencyByCode[$district['regency_code']] ?? null;
        if ($regency === null) {
            return null;
        }

        $province = $this->provinceByCode[$regency['province_code']] ?? null;
        if ($province === null) {
            return null;
        }

        return new RegionHierarchy(
            new RegionUnit($province['code'], $province['name']),
            new RegionUnit($regency['code'], $regency['name']),
            new RegionUnit($district['code'], $district['name']),
        );
    }

    public function findProvinceByName(string $term): ?array
    {
        $this->ensureLoaded();
        $needle = $this->normalizeName($term);
        if (blank($needle)) {
            return null;
        }

        $code = $this->provinceCodeByNormalizedName[$needle] ?? null;
        if ($code !== null) {
            return ['code' => $code, 'name' => $this->provinceByCode[$code]['name']];
        }

        $needleTokens = $this->meaningfulTokens($needle);
        if ($needleTokens === []) {
            return null;
        }

        foreach ($this->provinceByCode as $row) {
            if ($this->nameTokensSubset($needleTokens, $row['name'])) {
                return ['code' => $row['code'], 'name' => $row['name']];
            }
        }

        return null;
    }

    public function findRegencyByName(string $term, ?string $provinceCode = null): ?array
    {
        $this->ensureLoaded();
        $needle = $this->normalizeName($term);
        if (blank($needle)) {
            return null;
        }

        $exactCodes = $this->regencyCodesByNormalizedName[$needle] ?? null;
        if ($exactCodes !== null) {
            foreach ($exactCodes as $code) {
                $row = $this->regencyByCode[$code];
                if (! filled($provinceCode) || $row['province_code'] === $provinceCode) {
                    return ['code' => $row['code'], 'name' => $row['name']];
                }
            }
        }

        $needleTokens = $this->meaningfulTokens($needle);
        if ($needleTokens === []) {
            return null;
        }

        foreach ($this->regencyByCode as $row) {
            if (filled($provinceCode) && $row['province_code'] !== $provinceCode) {
                continue;
            }
            if ($this->nameTokensSubset($needleTokens, $row['name'])) {
                return ['code' => $row['code'], 'name' => $row['name']];
            }
        }

        return null;
    }

    public function findDistrictByName(string $term, ?string $regencyCode = null): ?array
    {
        $this->ensureLoaded();
        $needle = $this->normalizeName($term);
        if (blank($needle)) {
            return null;
        }

        $exactCodes = $this->districtCodesByNormalizedName[$needle] ?? null;
        if ($exactCodes !== null) {
            foreach ($exactCodes as $code) {
                $row = $this->districtByCode[$code];
                if (! filled($regencyCode) || $row['regency_code'] === $regencyCode) {
                    return ['code' => $row['code'], 'name' => $row['name']];
                }
            }
        }

        $needleTokens = $this->meaningfulTokens($needle);
        if ($needleTokens === []) {
            return null;
        }

        foreach ($this->districtByCode as $row) {
            if (filled($regencyCode) && $row['regency_code'] !== $regencyCode) {
                continue;
            }
            if ($this->nameTokensSubset($needleTokens, $row['name'])) {
                return ['code' => $row['code'], 'name' => $row['name']];
            }
        }

        return null;
    }

    private function normalizeName(string $name): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $name) ?? ''));
    }

    /**
     * @return list<string>
     */
    private function meaningfulTokens(string $normalizedUpper): array
    {
        $tokens = [];
        foreach (explode(' ', $normalizedUpper) as $token) {
            if (strlen($token) >= 3) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    /**
     * @param  list<string>  $needleTokens
     */
    private function nameTokensSubset(array $needleTokens, string $rowName): bool
    {
        $rowTokens = array_flip(explode(' ', $this->normalizeName($rowName)));
        foreach ($needleTokens as $token) {
            if (! isset($rowTokens[$token])) {
                return false;
            }
        }

        return true;
    }

    private function ensureLoaded(): void
    {
        if ($this->provinceByCode !== null) {
            return;
        }

        if (! is_file($this->dataFilePath)) {
            throw new \RuntimeException('Region hierarchy data file not found.');
        }

        /** @var array{provinces: list<array{code: string, name: string}>, regencies: list<array{code: string, province_code: string, name: string}>, districts: list<array{code: string, regency_code: string, name: string}>} $data */
        $data = require $this->dataFilePath;

        $this->provinceByCode = [];
        $this->provinceCodeByNormalizedName = [];
        foreach ($data['provinces'] as $row) {
            $this->provinceByCode[$row['code']] = $row;
            $this->provinceCodeByNormalizedName[$this->normalizeName($row['name'])] = $row['code'];
        }

        $this->regencyByCode = [];
        $this->regencyCodesByNormalizedName = [];
        foreach ($data['regencies'] as $row) {
            $this->regencyByCode[$row['code']] = $row;
            $this->regencyCodesByNormalizedName[$this->normalizeName($row['name'])][] = $row['code'];
        }

        $this->districtByCode = [];
        $this->districtCodesByNormalizedName = [];
        foreach ($data['districts'] as $row) {
            $this->districtByCode[$row['code']] = $row;
            $this->districtCodesByNormalizedName[$this->normalizeName($row['name'])][] = $row['code'];
        }
    }
}
