<?php

use Illuminate\Support\Facades\Route;
use Modules\Service\Http\Controllers\ServiceController;
use Modules\Service\Http\Controllers\ServicePackageController;

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
    // Route::apiResource('service', ServiceController::class)->names('service');
    Route::apiResource('service-packages', ServicePackageController::class);
    Route::get('service-packages/{package}/features', [ServicePackageController::class, 'showFeatures'])
        ->name('service-packages.features.show');
});
