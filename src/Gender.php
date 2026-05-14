<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';

    public static function tryFromMixed(Gender | string $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        $normalized = mb_strtolower(trim($value));

        return match ($normalized) {
            'm', 'male', 'man', 'l', 'laki-laki', 'laki laki' => self::Male,
            'f', 'female', 'woman', 'w', 'p', 'perempuan' => self::Female,
            default => null,
        };
    }

    public function same(Gender $other): bool
    {
        return $this === $other;
    }
}
