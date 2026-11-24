<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class PayOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['reference' => ['bail', 'required', 'string', 'max:64', 'regex:/^[A-Z0-9][A-Z0-9\-]{3,63}$/']];
    }

    public function messages(): array
    {
        return ['reference.regex' => 'A payment reference uses capital letters, digits and hyphens, for example TB-100234.'];
    }
}
