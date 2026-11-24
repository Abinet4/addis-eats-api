<?php

declare(strict_types=1);

use App\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

// ---- Day 52: unversioned service routes ----
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => 'addis-eats-api',
    'time' => now()->toIso8601String(),
]))->name('health');

Route::get('/areas', fn () => response()->json(array_keys(config('addiseats.zones'))))->name('areas');

Route::get('/quote', QuoteController::class)->name('quote');

// ---- Day 57: every versioned route lives in its own file ----
Route::prefix('v1')->name('v1.')->group(base_path('routes/api_v1.php'));
