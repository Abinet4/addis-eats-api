<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class EmptyOrderException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('An order needs at least one line.');
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error('empty_order', $this->getMessage(), 422);
    }
}
