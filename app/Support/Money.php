<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    /**
     * 24550 -> "245.50", 120000 -> "1200.00". Integer arithmetic only, so it is exact,
     * and no thousands separator, so a client can parse it. number_format($s / 100, 2)
     * would give "1,200.00" for anything from ETB 1,000 up.
     */
    public static function decimal(int $santim): string
    {
        $sign = $santim < 0 ? '-' : '';
        $abs = abs($santim);

        return sprintf('%s%d.%02d', $sign, intdiv($abs, 100), $abs % 100);
    }
}
