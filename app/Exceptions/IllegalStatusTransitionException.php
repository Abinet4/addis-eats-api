<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Domain\OrderStatus;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/** 409: the request is well formed but the world does not allow it right now. */
class IllegalStatusTransitionException extends \DomainException
{
    public function __construct(
        public readonly OrderStatus $from,
        public readonly OrderStatus $to,
    ) {
        parent::__construct("An order that is {$from->value} cannot become {$to->value}.");
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error('illegal_status_transition', $this->getMessage(), 409, [
            'from' => $this->from->value,
            'to' => $this->to->value,
        ]);
    }
}
