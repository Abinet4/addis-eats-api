<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class AlreadyPaidException extends \DomainException
{
    public function __construct(public readonly ?string $reference)
    {
        parent::__construct('This order is already paid with a different reference.');
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error('already_paid', $this->getMessage(), 409, ['payment_reference' => $this->reference]);
    }
}
