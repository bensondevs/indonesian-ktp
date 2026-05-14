<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\NIK;

use Bensondevs\IndonesianKtp\Gender;
use Bensondevs\IndonesianKtp\Regions\Data\RegionHierarchy;
use Bensondevs\IndonesianKtp\Regions\Lookup\RegionHierarchyLookup;
use Bensondevs\IndonesianKtp\Regions\Matching\NikRegionMatcher;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class Query
{
    private ?Carbon $expectedBirth = null;

    private ?Gender $expectedGender = null;

    private int | string | null $expectedProvince = null;

    private int | string | null $expectedRegency = null;

    private int | string | null $expectedSubdistrict = null;

    private ?int $expectedAge = null;

    private ?int $expectedMinAge = null;

    public function __construct(
        private readonly string $raw,
        private readonly CarbonInterface $evaluatedAt,
        private readonly bool $usePivotForYearResolution,
        private readonly RegionHierarchyLookup $regionHierarchyLookup,
    ) {}

    /**
     * Pin two-digit year resolution to a pivot instant (legacy reference-date behaviour).
     */
    public function asOf(CarbonInterface $date): self
    {
        return new self($this->raw, $date, true, $this->regionHierarchyLookup);
    }

    public function expectBirthDate(CarbonInterface | string $value): self
    {
        $clone = clone $this;
        $clone->expectedBirth = NikRegionMatcher::parseExpectedDate($value);

        return $clone;
    }

    public function birthDate(CarbonInterface | string $value): self
    {
        return $this->expectBirthDate($value);
    }

    public function expectGender(Gender | string $value): self
    {
        $clone = clone $this;
        if ($value instanceof Gender) {
            $clone->expectedGender = $value;
        } else {
            $gender = Gender::tryFromMixed($value);
            if ($gender === null) {
                throw new \InvalidArgumentException('Unrecognized gender value: ' . $value);
            }
            $clone->expectedGender = $gender;
        }

        return $clone;
    }

    public function gender(Gender | string $value): self
    {
        return $this->expectGender($value);
    }

    public function expectProvince(int | string $value): self
    {
        $clone = clone $this;
        $clone->expectedProvince = $value;

        return $clone;
    }

    public function province(int | string $value): self
    {
        return $this->expectProvince($value);
    }

    public function expectRegency(int | string $value): self
    {
        $clone = clone $this;
        $clone->expectedRegency = $value;

        return $clone;
    }

    public function regency(int | string $value): self
    {
        return $this->expectRegency($value);
    }

    public function expectSubdistrict(int | string $value): self
    {
        $clone = clone $this;
        $clone->expectedSubdistrict = $value;

        return $clone;
    }

    public function subdistrict(int | string $value): self
    {
        return $this->expectSubdistrict($value);
    }

    public function expectAge(int $years): self
    {
        if ($years < 0) {
            throw new \InvalidArgumentException('Expected age cannot be negative.');
        }

        $clone = clone $this;
        $clone->expectedAge = $years;

        return $clone;
    }

    /**
     * @param  int  $years  Expected completed full years of age (same instant as {@see evaluatedAt()}).
     */
    public function age(int $years): self
    {
        return $this->expectAge($years);
    }

    public function expectAtLeastYears(int $minYears): self
    {
        if ($minYears < 0) {
            throw new \InvalidArgumentException('Minimum age cannot be negative.');
        }

        $clone = clone $this;
        $clone->expectedMinAge = $minYears;

        return $clone;
    }

    public function expectSeventeenOrOlder(): self
    {
        return $this->expectAtLeastYears(17);
    }

    public function expectTwentyOneOrOlder(): self
    {
        return $this->expectAtLeastYears(21);
    }

    public function matchBirthDate(CarbonInterface | string $value): bool
    {
        $parsed = Parser::parse($this->raw, $this->evaluatedAt, $this->usePivotForYearResolution);

        return $parsed->birthDateEqualsDate($value);
    }

    public function matchGender(Gender | string $value): bool
    {
        $parsed = Parser::parse($this->raw, $this->evaluatedAt, $this->usePivotForYearResolution);

        return $parsed->genderEquals($value);
    }

    public function matchAge(int $years): bool
    {
        $parsed = Parser::parse($this->raw, $this->evaluatedAt, $this->usePivotForYearResolution);

        return $parsed->ageEquals($years, $this->evaluatedAt);
    }

    public function matchAtLeastYears(int $minYears): bool
    {
        if ($minYears < 0) {
            return false;
        }

        $parsed = Parser::parse($this->raw, $this->evaluatedAt, $this->usePivotForYearResolution);

        return $parsed->isAtLeastYears($minYears, $this->evaluatedAt);
    }

    public function matchSeventeenOrOlder(): bool
    {
        return $this->matchAtLeastYears(17);
    }

    public function matchTwentyOneOrOlder(): bool
    {
        return $this->matchAtLeastYears(21);
    }

    public function resolvedAge(): ?int
    {
        return $this->parsed()->age($this->evaluatedAt);
    }

    public function isAtLeastYears(int $minYears): bool
    {
        return $this->matchAtLeastYears($minYears);
    }

    public function isValid(): bool
    {
        return $this->validate()->isFullyValid();
    }

    public function validate(): ValidationResult
    {
        $parsed = Parser::parse($this->raw, $this->evaluatedAt, $this->usePivotForYearResolution);
        $matcher = new NikRegionMatcher($this->regionHierarchyLookup);
        $structureValid = $parsed->structureValid();
        $districtCode = $parsed->districtCode();

        $hierarchy = $structureValid && filled($districtCode)
            ? $this->regionHierarchyLookup->hierarchy($districtCode)
            : null;

        return new ValidationResult(
            hasValidStructure: $structureValid,
            hasValidRegionHierarchy: $this->hierarchyIsComplete($hierarchy),
            hasValidBirthDate: blank($this->expectedBirth)
                ? null
                : ($structureValid && $parsed->birthDateEqualsDate($this->expectedBirth)),
            hasValidGender: blank($this->expectedGender)
                ? null
                : ($structureValid
                    && filled($parsed->gender())
                    && $parsed->gender() === $this->expectedGender),
            hasValidProvince: blank($this->expectedProvince)
                ? null
                : ($structureValid && $matcher->provinceMatches($hierarchy, $this->expectedProvince)),
            hasValidRegency: blank($this->expectedRegency)
                ? null
                : ($structureValid && $matcher->regencyMatches($hierarchy, $this->expectedRegency)),
            hasValidSubdistrict: blank($this->expectedSubdistrict)
                ? null
                : ($structureValid && $matcher->subdistrictMatches($hierarchy, $this->expectedSubdistrict)),
            hasValidAge: $this->expectedAge === null
                ? null
                : ($structureValid && $parsed->ageEquals($this->expectedAge, $this->evaluatedAt)),
            hasValidMinimumAge: $this->expectedMinAge === null
                ? null
                : ($structureValid && $parsed->isAtLeastYears($this->expectedMinAge, $this->evaluatedAt)),
        );
    }

    private function hierarchyIsComplete(?RegionHierarchy $hierarchy): bool
    {
        return filled($hierarchy);
    }

    public function parsed(): Parsed
    {
        return Parser::parse($this->raw, $this->evaluatedAt, $this->usePivotForYearResolution);
    }

    public function evaluatedAt(): CarbonInterface
    {
        return $this->evaluatedAt;
    }
}
