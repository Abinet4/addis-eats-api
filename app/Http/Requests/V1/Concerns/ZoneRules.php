<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Concerns;

trait ZoneRules
{
    /** @return list<string> The zone list lives in exactly one place: config/addiseats.php */
    protected function zones(): array
    {
        return array_keys(config('addiseats.zones'));
    }
}
