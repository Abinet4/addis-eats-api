<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Http\Requests\V1\Concerns\NormalizesPhone;
use App\Http\Requests\V1\Concerns\ZoneRules;
use App\Rules\TeleBirrNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreRestaurantRequest extends FormRequest
{
    use NormalizesPhone, ZoneRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePhone();

        // Generate the slug HERE, not only in the model, so the unique rule can see it.
        // Otherwise a second "Sishu" passes validation and dies on the UNIQUE index (a 500).
        // Str::slug() of an Amharic-only name is "", which the rules below turn into a 422.
        if ($this->generatesSlug() && ! $this->filled('slug') && is_string($this->input('name'))) {
            $this->merge(['slug' => Str::slug($this->input('name'))]);
        }
    }

    protected function generatesSlug(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:120'],
            // optional: generated from the name when absent
            'slug' => ['bail', 'required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('restaurants', 'slug')->ignore($this->route('restaurant'))],
            'area' => ['bail', 'required', 'string', Rule::in($this->zones())],
            'phone' => ['bail', 'required', new TeleBirrNumber],
            'opening_hours' => ['sometimes', 'nullable', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d-([01]\d|2[0-3]):[0-5]\d$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'area.in' => 'Addis Eats does not deliver to that area yet.',
            'slug.regex' => 'A slug may contain only lower-case letters, digits and single hyphens.',
            'slug.required' => 'Send a slug in Latin letters, for example yod-abyssinia; one could not be made from this name.',
            'opening_hours.regex' => 'Write opening hours as HH:MM-HH:MM, for example 08:00-22:00.',
        ];
    }
}
