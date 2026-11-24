<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class UnknownDishException extends \DomainException
{
    /** @param list<int> $ids */
    public function __construct(public readonly array $ids)
    {
        parent::__construct('Some dishes do not exist or were withdrawn: '.implode(', ', $ids).'.');
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error('unknown_dish', $this->getMessage(), 422, ['dish_ids' => $this->ids]);
    }
}
