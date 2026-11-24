<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\Request;

/** The query-string convention shared by every list endpoint (Days 56-57). */
trait ListsResources
{
    /** per_page: default 15, at least 1, at most 100. 0, negatives and "abc" fall back to safe values. */
    protected function perPage(Request $request): int
    {
        $default = (int) config('addiseats.pagination.default_per_page', 15);
        $max = (int) config('addiseats.pagination.max_per_page', 100);
        $asked = $request->integer('per_page', $default);

        return $asked < 1 ? $default : min($asked, $max);
    }

    /**
     * ?sort=price or ?sort=-price, checked against a whitelist before it can reach SQL.
     *
     * @param  list<string>  $sortable
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    protected function sort(Request $request, array $sortable, string $default): array
    {
        $sort = $request->string('sort', $default)->trim()->toString();
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (! in_array($column, $sortable, true)) {
            return [ltrim($default, '-'), str_starts_with($default, '-') ? 'desc' : 'asc'];
        }

        return [$column, $direction];
    }
}
