<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
});

Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
Route::get('/products/{product}', [\App\Http\Controllers\Api\ProductController::class, 'show']);

Route::middleware('jwt.auth')->group(function () {
    Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::post('/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('/orders/{order}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::post('/orders/{order}/payment', [\App\Http\Controllers\Api\OrderController::class, 'startPayment']);
    Route::post('/orders/{order}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel']);

    Route::middleware('api.admin')->group(function () {
        Route::patch('/orders/{order}/status', [\App\Http\Controllers\Api\OrderController::class, 'updateStatus']);
        Route::post('/inventory/restock', [\App\Http\Controllers\Api\InventoryController::class, 'restock']);
        Route::get('/reports/low-stock', [\App\Http\Controllers\Api\InventoryController::class, 'lowStock']);
        Route::get('/reports/daily-sales', [\App\Http\Controllers\Api\InventoryController::class, 'dailySales']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
