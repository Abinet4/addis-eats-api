<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Exceptions\OutsideZoneException;

/** The single source of truth for which areas are served and at what base fee. */
final class DeliveryZones
{
    /** @param array<string, int> $fees area => base fee in santim */
    public function __construct(
        private readonly array $fees,
        public readonly int $freeDeliveryFrom,
    ) {}

    public function serves(string $area): bool
    {
        return array_key_exists($area, $this->fees);
    }

    /** @throws OutsideZoneException */
    public function baseFee(string $area): int
    {
        return $this->fees[$area] ?? throw new OutsideZoneException($area);
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->fees);
    }
}
