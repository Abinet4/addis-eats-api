<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\DishController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\OrderCancellationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderPaymentController;
use App\Http\Controllers\Api\V1\OrderStatusController;
use App\Http\Controllers\Api\V1\RestaurantController;
use App\Http\Controllers\Api\V1\RestaurantDishController;
use Illuminate\Support\Facades\Route;

// Loaded by routes/api.php inside prefix('v1')->name('v1.'), so every URI is /api/v1/...
// Parameter constraints (dish, order numeric; restaurant slug) are global, in AppServiceProvider::boot().

Route::get('/menu', MenuController::class)->name('menu');

Route::apiResource('dishes', DishController::class);

Route::apiResource('restaurants', RestaurantController::class);

// Shallow nesting, reduced to the two actions that need the parent. show/update/destroy
// already exist as /dishes/{dish}; registering them again would duplicate those routes.
Route::apiResource('restaurants.dishes', RestaurantDishController::class)->only(['index', 'store']);

Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
Route::patch('/orders/{order}/status', OrderStatusController::class)->name('orders.status');
Route::post('/orders/{order}/cancellation', OrderCancellationController::class)->name('orders.cancellation');
Route::put('/orders/{order}/payment', OrderPaymentController::class)->name('orders.payment');
