<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Concerns;

/**
 * Normalise, never correct: "+251 911-234 567" and "251911234567" become "0911234567";
 * "911234567" stays as it is and fails validation, because guessing a missing digit
 * about somebody's money will one day guess wrong.
 */
trait NormalizesPhone
{
    protected function normalizePhone(string $field = 'phone'): void
    {
        if (! $this->has($field) || ! is_string($this->input($field))) {
            return; // absent or not a string: leave it for the rules to report
        }

        $p = preg_replace('/[\s\-().]/', '', $this->input($field));
        $p = preg_replace('/^(\+251|00251|251)(?=9\d{8}$)/', '0', $p);

        $this->merge([$field => $p]);
    }
}
