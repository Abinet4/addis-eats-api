<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Money as a whole number of santim. Stricter than Laravel's "integer" rule, which
 * accepts JSON true (as 1) and 685.0 (a float), both of which are bugs for money.
 */
final class Santim implements ValidationRule
{
    public function __construct(
        private readonly int $min = 1,
        private readonly int $max = 1_000_000, // ETB 10,000.00
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isWhole = is_int($value) || (is_string($value) && preg_match('/^\d+$/', $value) === 1);

        if (! $isWhole) {
            $fail('The :attribute must be a whole number of santim, for example 68500 for ETB 685.00.');

            return;
        }

        $santim = (int) $value;
        if ($santim < $this->min || $santim > $this->max) {
            $fail("The :attribute must be between {$this->min} and {$this->max} santim.");
        }
    }
}
