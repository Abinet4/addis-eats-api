<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class OutsideZoneException extends \DomainException
{
    public function __construct(public readonly string $area)
    {
        parent::__construct("Addis Eats does not deliver to {$area} yet.");
    }

    /** Laravel calls render() automatically, so no controller needs a try/catch for this. */
    public function render(): JsonResponse
    {
        return ApiResponse::error('outside_delivery_zone', $this->getMessage(), 422, ['area' => $this->area]);
    }
}
