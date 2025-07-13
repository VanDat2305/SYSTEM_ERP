<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\ContractController;
use Modules\Order\Http\Controllers\DashboardController;
use Modules\Order\Http\Controllers\InvoiceController;
use Modules\Order\Http\Controllers\OrderController;
use Modules\Order\Http\Controllers\OrderLogController;
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
    Route::group(['prefix' => 'orders'], function () {
         Route::post('/{order}/export-invoice', [InvoiceController::class, 'export'])->middleware('can:orders.export-invoice');
        Route::post('/{order}/resend-invoice', [InvoiceController::class, 'resendInvoice'])->middleware('can:orders.resend-invoice');
        Route::post('/{order}/export-contract', [ContractController::class, 'exportContract'])->middleware('can:orders.export-contract');
        Route::post('/{order}/upload-file-signed', [ContractController::class, 'uploadFileSigned']);//->middleware('can:orders.upload-file-signed');
        Route::delete('/{order}/file', [ContractController::class, 'deleteFileContract']);//->middleware('can:orders.delete-file-signed');
        Route::post('{order}/add-payment', [InvoiceController::class, 'addPayment']);//->middleware('can:orders.add-payment');
        Route::post('/{order}/logs/note', [OrderLogController::class, 'store']);
        Route::get('/{order}/logs', [OrderLogController::class, 'index']);

        Route::post('/{order}/send-contract-mail', [ContractController::class, 'sendContract']);//->middleware('can:orders.send-contract-mail');
    });
    Route::get('/order-details/{id}/prepare-renew', [OrderController::class, 'prepareRenew']);
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/kpi', [DashboardController::class, 'kpi']);
        Route::get('/revenue', [DashboardController::class, 'revenue']);
        Route::get('/orders', [DashboardController::class, 'orders']);  
        Route::get('/customers', [DashboardController::class, 'customers']);
        Route::get('/analytics', [DashboardController::class, 'analytics']);
        Route::get('/customer-growth', [DashboardController::class, 'customerGrowth']);
        Route::get('/export', [DashboardController::class, 'export']);
    });
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


    // Quota API
    Route::prefix('quota')->middleware('basic_auth')->group(function () {
        Route::get('check', [\Modules\Order\Http\Controllers\QuotaController::class, 'check']);
        
        Route::post('warning', [\Modules\Order\Http\Controllers\QuotaController::class, 'warning']);
    });

    Route::middleware('auth:account_api')->post('quota/use', [\Modules\Order\Http\Controllers\QuotaController::class, 'use']);
});
