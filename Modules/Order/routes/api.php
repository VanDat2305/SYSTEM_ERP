<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\ContractController;
use Modules\Order\Http\Controllers\InvoiceController;
use Modules\Order\Http\Controllers\OrderController;
use Modules\Order\Http\Controllers\PaymentController;
use Modules\Order\Http\Controllers\VnpayController;

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
    Route::patch("orders-update-status", [OrderController::class, 'bulkStatusUpdate']);
    Route::patch("orders/{order}/status", [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/export-invoice', [InvoiceController::class, 'export'])->middleware('can:orders.export-invoice');
    Route::post('/orders/{order}/resend-invoice', [InvoiceController::class, 'resendInvoice'])->middleware('can:orders.resend-invoice');
    Route::post('/orders/{order}/export-contract', [ContractController::class, 'exportContract']);//->middleware('can:orders.export-contract');
});
Route::prefix('v1')->group(function () {

    Route::middleware('basic_auth')->group(function (){
        Route::post('/payment/initiate', [PaymentController::class, 'initiate']);
        Route::get('/payment/methods', [PaymentController::class, 'methods']);
        Route::get('/orders/code/{order_code}', [PaymentController::class, 'findByCode']);
    });

    Route::post('/payment/vnpay/initiate', [VnpayController::class, 'initiateVnpay']);
    Route::get('/payment/vnpay/return', [VnpayController::class, 'handleReturn']);
    Route::match(['get','post'], '/payment/vnpay/ipn', [VnpayController::class, 'handleIpn']);
});
