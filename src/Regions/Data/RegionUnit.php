<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\Regions\Data;

final readonly class RegionUnit
{
    public function __construct(
        public string $code,
        public string $name,
    ) {}
}
