<?php

declare(strict_types=1);

namespace Bensondevs\IndonesianKtp\Rules;

use Bensondevs\IndonesianKtp\KTP;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;

final class KtpNik implements ValidationRule, ValidatorAwareRule
{
    protected Validator $validator;

    /**
     * Plain structural + wilayah validation (same semantics as {@see KTP::nik()} with no expectations).
     *
     * @return bool True when the value is skipped (null / empty string), valid NIK, or acceptable scalar form.
     */
    public static function passes(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_bool($value) || is_array($value) || is_object($value)) {
            return false;
        }

        $raw = is_string($value) ? $value : (string) $value;

        return KTP::nik($raw)->isValid();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::passes($value)) {
            $fail('indonesian-ktp::validation.ktp_nik')->translate([
                'attribute' => $this->validator->getDisplayableAttribute($attribute),
            ]);
        }
    }

    /**
     * @return $this
     */
    public function setValidator(Validator $validator)
    {
        $this->validator = $validator;

        return $this;
    }
}
