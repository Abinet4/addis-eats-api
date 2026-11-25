<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

/**
 * PATCH: every rule starts with "sometimes", so a client changing only the price is
 * not told that name is required. PUT: a replacement, so the Store rules apply as written.
 */
class UpdateDishRequest extends StoreDishRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        if ($this->isMethod('put')) {
            return $rules;
        }

        return array_map(fn (array $r) => ['sometimes', ...array_values(array_filter($r, fn ($rule) => $rule !== 'required'))], $rules);
    }
}
