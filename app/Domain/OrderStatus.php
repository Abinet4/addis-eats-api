<?php

declare(strict_types=1);

namespace App\Domain;

enum OrderStatus: string
{
    case Placed = 'placed';
    case Preparing = 'preparing';
    case OnTheWay = 'on_the_way';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled'; // added on Day 57 for the cancellation action

    public function label(): string
    {
        return match ($this) {
            self::Placed => 'Placed',
            self::Preparing => 'Being prepared',
            self::OnTheWay => 'On the way',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /** The legal state machine. Anything not listed is a 409 Conflict. */
    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Placed => [self::Preparing, self::Cancelled],
            self::Preparing => [self::OnTheWay, self::Cancelled],
            self::OnTheWay => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        }, true);
    }
}
