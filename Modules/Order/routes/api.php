<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\OrderController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('orders', OrderController::class);
    Route::prefix('orders/{order}')->group(function () {
        Route::get('details', [OrderController::class, 'getOrderDetails']);
        Route::post('details', [OrderController::class, 'addOrderDetails']);
    });

    Route::prefix('order-details/{orderDetail}')->group(function () {
        Route::put('', [OrderController::class, 'updateOrderDetail']);
        Route::delete('', [OrderController::class, 'deleteOrderDetail']);
    });
});
