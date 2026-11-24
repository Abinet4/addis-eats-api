<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/** 409: the dish existed when the client loaded the menu, but it has been withdrawn since. */
class DishWithdrawnException extends \DomainException
{
    /** @param list<int> $ids */
    public function __construct(public readonly array $ids)
    {
        parent::__construct('Some dishes in this order are no longer available. Refresh the menu and try again.');
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error('dish_withdrawn', $this->getMessage(), 409, ['dish_ids' => $this->ids]);
    }
}
