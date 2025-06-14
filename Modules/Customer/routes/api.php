<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\CustomerController;
use Modules\Customer\Http\Controllers\CustomerContactController;
use Modules\Customer\Http\Controllers\CustomerRepresentativeController;
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
    // Customer routes
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->middleware('permission:customers.view');
        Route::post('/', [CustomerController::class, 'store'])->middleware('permission:customers.create');
        Route::get('/search', [CustomerController::class, 'search'])->middleware('permission:customers.view');
        Route::get('/type/{type}', [CustomerController::class, 'getByType'])->middleware('permission:customers.view');
        Route::get('/{id}', [CustomerController::class, 'show'])->middleware('permission:customers.view');
        Route::put('/{id}', [CustomerController::class, 'update'])->middleware('permission:customers.update');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->middleware('permission:customers.delete');
        // Route::put('/{id}/toggle-status', [CustomerController::class, 'toggleStatus'])->middleware('permission:customers.toggle_status');

        // Customer contacts
        Route::get('/{customerId}/contacts', [CustomerContactController::class, 'index'])->middleware('permission:customers.contact.manage');
        Route::post('/contacts', [CustomerContactController::class, 'store'])->middleware('permission:customers.contact.manage');
        Route::put('/contacts/{id}', [CustomerContactController::class, 'update'])->middleware('permission:customers.contact.manage');
        Route::delete('/contacts/{id}', [CustomerContactController::class, 'destroy'])->middleware('permission:customers.contact.manage');
        Route::put('/{customerId}/contacts/{contactId}/set-primary', [CustomerContactController::class, 'setPrimary'])->middleware('permission:customers.contact.manage');

        // Customer representatives
        Route::get('/{customerId}/representatives', [CustomerRepresentativeController::class, 'index'])->middleware('permission:customers.representative.manage');
        Route::post('/representatives', [CustomerRepresentativeController::class, 'store'])->middleware('permission:customers.representative.manage');
        Route::put('/representatives/{id}', [CustomerRepresentativeController::class, 'update'])->middleware('permission:customers.representative.manage');
        Route::delete('/representatives/{id}', [CustomerRepresentativeController::class, 'destroy'])->middleware('permission:customers.representative.manage');
    });
});
