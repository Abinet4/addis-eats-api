<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Domain\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Day 62: only a driver may send "delivered"
    }

    public function rules(): array
    {
        return [
            // placed is the start state and cancelled has its own endpoint (it carries a reason)
            'status' => ['required', Rule::enum(OrderStatus::class)->except([OrderStatus::Placed, OrderStatus::Cancelled])],
        ];
    }

    public function messages(): array
    {
        return ['status.enum' => 'Status must be one of preparing, on_the_way or delivered.'];
    }
}
