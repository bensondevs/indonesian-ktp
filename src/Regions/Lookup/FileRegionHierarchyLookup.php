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

    /** @var list<array{code: string, name: string}>|null */
    private ?array $provinceList = null;

    /** @var list<array{code: string, province_code: string, name: string}>|null */
    private ?array $regencyList = null;

    /** @var list<array{code: string, regency_code: string, name: string}>|null */
    private ?array $districtList = null;

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
            new RegionUnit((string) $province['code'], (string) $province['name']),
            new RegionUnit((string) $regency['code'], (string) $regency['name']),
            new RegionUnit((string) $district['code'], (string) $district['name']),
        );
    }

    public function findProvinceByName(string $term): ?array
    {
        $this->ensureLoaded();
        $needle = $this->normalizeName($term);
        if (blank($needle)) {
            return null;
        }

        foreach ($this->provinceList as $row) {
            $normalizedName = $this->normalizeName($row['name']);
            if ($normalizedName === $needle || str_contains($normalizedName, $needle) || str_contains($needle, $normalizedName)) {
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

        foreach ($this->regencyList as $row) {
            if (filled($provinceCode) && $row['province_code'] !== $provinceCode) {
                continue;
            }
            $normalizedName = $this->normalizeName($row['name']);
            if ($normalizedName === $needle || str_contains($normalizedName, $needle) || str_contains($needle, $normalizedName)) {
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

        foreach ($this->districtList as $row) {
            if (filled($regencyCode) && $row['regency_code'] !== $regencyCode) {
                continue;
            }
            $normalizedName = $this->normalizeName($row['name']);
            if ($normalizedName === $needle || str_contains($normalizedName, $needle) || str_contains($needle, $normalizedName)) {
                return ['code' => $row['code'], 'name' => $row['name']];
            }
        }

        return null;
    }

    private function normalizeName(string $name): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim($name)) ?? '');
    }

    private function ensureLoaded(): void
    {
        if ($this->provinceByCode !== null) {
            return;
        }

        if (! is_file($this->dataFilePath)) {
            throw new \RuntimeException('Region hierarchy data file not found: ' . $this->dataFilePath);
        }

        /** @var array{provinces: list<array{code: string, name: string}>, regencies: list<array{code: string, province_code: string, name: string}>, districts: list<array{code: string, regency_code: string, name: string}>} $data */
        $data = require $this->dataFilePath;

        $this->provinceByCode = [];
        $this->provinceList = $data['provinces'];
        foreach ($data['provinces'] as $row) {
            $this->provinceByCode[$row['code']] = $row;
        }

        $this->regencyByCode = [];
        $this->regencyList = $data['regencies'];
        foreach ($data['regencies'] as $row) {
            $this->regencyByCode[$row['code']] = $row;
        }

        $this->districtByCode = [];
        $this->districtList = $data['districts'];
        foreach ($data['districts'] as $row) {
            $this->districtByCode[$row['code']] = $row;
        }
    }
}
