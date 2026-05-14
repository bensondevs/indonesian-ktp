<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\Regions\Data;

final readonly class RegionHierarchy
{
    public function __construct(
        public RegionUnit $province,
        public RegionUnit $regency,
        public RegionUnit $district,
    ) {}
}
