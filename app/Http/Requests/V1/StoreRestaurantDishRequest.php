<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Rules\Santim;
use Illuminate\Foundation\Http\FormRequest;

/** POST /restaurants/{slug}/dishes: restaurant and area come from the URL, not the body. */
class StoreRestaurantDishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:120'],
            'price' => ['bail', 'required', new Santim],
            'is_fasting' => ['sometimes', 'boolean'],
        ];
    }
}
