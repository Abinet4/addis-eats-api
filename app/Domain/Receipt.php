<?php

declare(strict_types=1);

namespace App\Domain;

use App\Support\FormatsEtb;

/** Renders an Order as text. Shares FormatsEtb with Order without sharing a parent class. */
final class Receipt
{
    use FormatsEtb;

    public function __construct(private readonly Order $order) {}

    public function render(): string
    {
        $o = $this->order;

        // fee() is computed first so an empty or out-of-zone order fails before printing anything
        $fee = $o->fee();

        $lines = $o->lines()
            ->map(fn (OrderLine $l) => sprintf(
                '  %-12s x%-2d %14s',
                $l->dish->name,
                $l->qty,
                $this->toEtb($l->amount()),
            ))
            ->implode(PHP_EOL);

        return implode(PHP_EOL, [
            "Receipt for order #{$o->id} ({$o->area})",
            $lines,
            sprintf('  %-16s %14s', 'Subtotal', $this->toEtb($o->subtotal())),
            sprintf('  %-16s %14s', 'Delivery fee', $this->toEtb($fee)),
            sprintf('  %-16s %14s', 'Total', $this->toEtb($o->subtotal() + $fee)),
            '  Status: '.$o->status()->label(),
        ]);
    }
}
