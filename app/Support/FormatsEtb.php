<?php

declare(strict_types=1);

namespace App\Support;

trait FormatsEtb
{
    /** 68500 -> "685.00 ETB". Format only for display, never before arithmetic. */
    public function toEtb(int $santim): string
    {
        return number_format($santim / 100, 2).' ETB';
    }
}
