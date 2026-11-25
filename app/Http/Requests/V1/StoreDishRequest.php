<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Http\Requests\V1\Concerns\ZoneRules;
use App\Rules\Santim;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDishRequest extends FormRequest
{
    use ZoneRules;

    public function authorize(): bool
    {
        return true; // roles arrive on Day 62
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:120'],
            'restaurant_id' => ['bail', 'required', 'integer', 'numeric', Rule::exists('restaurants', 'id')],
            'price' => ['bail', 'required', new Santim],
            'is_fasting' => ['sometimes', 'boolean'],
            'area' => ['bail', 'required', 'string', Rule::in($this->zones())],
        ];
    }

    public function messages(): array
    {
        return ['area.in' => 'Addis Eats does not deliver to that area yet.'];
    }

    public function attributes(): array
    {
        return ['restaurant_id' => 'restaurant'];
    }
}
