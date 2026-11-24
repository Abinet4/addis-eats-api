<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An Ethio Telecom mobile number as TeleBirr settles it: 09 followed by eight digits.
 * Input is normalised first by NormalizesPhone (spaces, hyphens, +251), never "corrected".
 */
final class TeleBirrNumber implements ValidationRule
{
    public const PATTERN = '/^09\d{8}$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::PATTERN, $value) !== 1) {
            $fail('Enter a TeleBirr number as 09XXXXXXXX.');
        }
    }
}
