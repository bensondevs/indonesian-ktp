<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\NIK;

final readonly class ValidationResult
{
    public function __construct(
        private bool $hasValidStructure,
        private bool $hasValidRegionHierarchy,
        private ?bool $hasValidBirthDate,
        private ?bool $hasValidGender,
        private ?bool $hasValidProvince,
        private ?bool $hasValidRegency,
        private ?bool $hasValidSubdistrict,
        private ?bool $hasValidAge,
        private ?bool $hasValidMinimumAge,
    ) {}

    public function hasValidStructure(): bool
    {
        return $this->hasValidStructure;
    }

    public function hasValidRegionHierarchy(): bool
    {
        return $this->hasValidRegionHierarchy;
    }

    public function hasValidBirthDate(): ?bool
    {
        return $this->hasValidBirthDate;
    }

    public function hasValidGender(): ?bool
    {
        return $this->hasValidGender;
    }

    public function hasValidProvince(): ?bool
    {
        return $this->hasValidProvince;
    }

    public function hasValidRegency(): ?bool
    {
        return $this->hasValidRegency;
    }

    public function hasValidSubdistrict(): ?bool
    {
        return $this->hasValidSubdistrict;
    }

    public function hasValidAge(): ?bool
    {
        return $this->hasValidAge;
    }

    public function hasValidMinimumAge(): ?bool
    {
        return $this->hasValidMinimumAge;
    }

    public function isFullyValid(): bool
    {
        if (! $this->hasValidStructure || ! $this->hasValidRegionHierarchy) {
            return false;
        }

        return collect([
            $this->hasValidBirthDate,
            $this->hasValidGender,
            $this->hasValidProvince,
            $this->hasValidRegency,
            $this->hasValidSubdistrict,
            $this->hasValidAge,
            $this->hasValidMinimumAge,
        ])->every(static fn (?bool $expectation): bool => $expectation !== false);
    }

    public function hasValidKabupaten(): ?bool
    {
        return $this->hasValidRegency;
    }

    public function hasValidCity(): ?bool
    {
        return $this->hasValidRegency;
    }

    public function hasValidKecamatan(): ?bool
    {
        return $this->hasValidSubdistrict;
    }
}
