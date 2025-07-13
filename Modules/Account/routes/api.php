<?php

use Illuminate\Support\Facades\Route;
use Modules\Account\Http\Controllers\AccountController;
use Modules\Account\Http\Controllers\AccountServiceController;

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

// Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
//     Route::apiResource('account', AccountController::class)->names('account');
// });
Route::middleware('basic_auth')->post('/v1/account/check-or-create', [AccountController::class, 'checkOrCreate']);
Route::post('/v1/account/login', [AccountController::class, 'login']);
Route::middleware('auth:account_api')->get('/v1/account/services', [AccountServiceController::class, 'services']);
