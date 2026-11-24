<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/** 409: the payment reference already settled a different order. */
class PaymentReferenceTakenException extends \DomainException
{
    public function __construct(public readonly string $reference)
    {
        parent::__construct('This payment reference has already been used for another order.');
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error('payment_reference_taken', $this->getMessage(), 409, ['reference' => $this->reference]);
    }
}
