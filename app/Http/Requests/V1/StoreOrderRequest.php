<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Http\Requests\V1\Concerns\NormalizesPhone;
use App\Http\Requests\V1\Concerns\ZoneRules;
use App\Models\Dish;
use App\Rules\TeleBirrNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    use NormalizesPhone, ZoneRules;

    public const MAX_LINES = 30;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePhone();
        if (is_string($this->input('area'))) {
            $this->merge(['area' => trim($this->input('area'))]);
        }
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['bail', 'required', 'string', 'max:120'],
            'phone' => ['bail', 'required', new TeleBirrNumber],
            'area' => ['bail', 'required', 'string', Rule::in($this->zones())],
            'lines' => ['bail', 'required', 'array', 'min:1', 'max:'.self::MAX_LINES],
            'lines.*' => ['bail', 'required', 'array:dish_id,qty'],
            // exists INCLUDES soft-deleted rows on purpose: a dish that never existed is a 422,
            // a dish withdrawn after the client loaded the menu is a 409 from OrderService.
            'lines.*.dish_id' => ['bail', 'required', 'integer', 'numeric', Rule::exists('dishes', 'id')],
            'lines.*.qty' => ['bail', 'required', 'integer', 'numeric', 'between:1,50'],
        ];
    }

    /** Cross-field check: runs only after every field rule above has passed. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $ids = collect($this->input('lines'))->pluck('dish_id')->unique();
                $areas = Dish::withTrashed()->whereIn('id', $ids)->distinct()->pluck('area');

                if ($areas->count() > 1) {
                    $validator->errors()->add('lines', 'All dishes in one order must be from the same area.');
                }

                // The service merges repeated dishes into one line, so the 1 to 50 cap must
                // hold for the merged quantity too, or two lines of 50 become one line of 100.
                $over = collect($this->input('lines'))->groupBy('dish_id')
                    ->filter(fn ($g) => $g->sum('qty') > 50)->keys();
                if ($over->isNotEmpty()) {
                    $validator->errors()->add('lines', 'At most 50 of one dish per order (dish '.$over->implode(', ').').');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'area.in' => 'Addis Eats does not deliver to that area yet.',
            'lines.min' => 'An order needs at least one line.',
            'lines.max' => 'An order may have at most '.self::MAX_LINES.' lines.',
            'lines.*.qty.between' => 'Each quantity must be between 1 and 50.',
            'lines.*.array' => 'Each line may contain only dish_id and qty. Prices are set by the server.',
        ];
    }

    public function attributes(): array
    {
        return ['lines.*.dish_id' => 'dish', 'lines.*.qty' => 'quantity'];
    }
}
