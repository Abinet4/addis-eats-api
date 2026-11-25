<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

class UpdateRestaurantRequest extends StoreRestaurantRequest
{
    /** Renaming a restaurant keeps its slug, so published URLs keep working. */
    protected function generatesSlug(): bool
    {
        return false;
    }

    public function rules(): array
    {
        $rules = parent::rules();

        if ($this->isMethod('put')) {
            return $rules;
        }

        return array_map(
            fn (array $r) => in_array('sometimes', $r, true) ? $r : ['sometimes', ...array_values(array_filter($r, fn ($rule) => $rule !== 'required'))],
            $rules,
        );
    }
}
