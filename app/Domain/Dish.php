<?php

declare(strict_types=1);

namespace App\Domain;

use App\Support\FormatsEtb;

/** A dish as a plain value object (Day 53). Eloquent's App\Models\Dish arrives on Day 54. */
final class Dish
{
    use FormatsEtb;

    public function __construct(
        public readonly string $name,
        public readonly string $restaurant,
        public readonly int $price,       // santim
        public readonly bool $isFasting,
    ) {
        if ($price < 0) {
            throw new \InvalidArgumentException('A price cannot be negative.');
        }
    }

    /** @param array{name: string, restaurant: string, price: int, is_fasting: bool} $row */
    public static function fromArray(array $row): self
    {
        return new self($row['name'], $row['restaurant'], $row['price'], $row['is_fasting']);
    }

    public function priceInEtb(): string
    {
        return $this->toEtb($this->price);
    }
}
