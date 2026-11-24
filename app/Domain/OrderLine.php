<?php

declare(strict_types=1);

namespace App\Domain;

final class OrderLine
{
    public function __construct(
        public readonly Dish $dish,
        public readonly int $qty,
    ) {
        if ($qty < 1) {
            throw new \InvalidArgumentException('Quantity must be 1 or greater.');
        }
    }

    public function amount(): int
    {
        return $this->dish->price * $this->qty;
    }
}
